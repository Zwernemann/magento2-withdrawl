<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\Email\Sender as EmailSender;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class Submit implements HttpPostActionInterface
{
    protected $request;
    protected $redirectFactory;
    protected $messageManager;
    protected $orderRepository;
    protected $dateTime;
    protected $customerSession;
    protected $config;
    protected $withdrawalRepository;
    protected $emailSender;
    protected $resource;
    protected $formKeyValidator;

    public function __construct(
        RequestInterface         $request,
        RedirectFactory          $redirectFactory,
        ManagerInterface         $messageManager,
        OrderRepositoryInterface $orderRepository,
        DateTime                 $dateTime,
        CustomerSession          $customerSession,
        Config                   $config,
        WithdrawalRepository     $withdrawalRepository,
        EmailSender              $emailSender,
        ResourceConnection       $resource,
        FormKeyValidator         $formKeyValidator
    )
    {
        $this->request = $request;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->dateTime = $dateTime;
        $this->customerSession = $customerSession;
        $this->config = $config;
        $this->withdrawalRepository = $withdrawalRepository;
        $this->emailSender = $emailSender;
        $this->resource = $resource;
        $this->formKeyValidator = $formKeyValidator;
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();

        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please try again.'));
            return $redirect->setPath('sales/order/history');
        }

        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('The withdrawal function is currently not available.'));
            return $redirect->setPath('sales/order/history');
        }

        $orderId = (int)$this->request->getParam('order_id');
        $isGuest = (bool)$this->request->getParam('guest');
        $guestEmail = $this->request->getParam('guest_email');

        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('No order specified.'));
            return $redirect->setPath('sales/order/history');
        }

        try {
            $order = $this->orderRepository->get($orderId);

            // Validate access: either logged-in customer owns order, or guest email matches
            if (!$isGuest) {
                if (!$this->customerSession->isLoggedIn()) {
                    $this->messageManager->addErrorMessage(__('Please log in to submit a withdrawal.'));
                    return $redirect->setPath('customer/account/login');
                }
                $customerId = $this->customerSession->getCustomerId();
                if ((int)$order->getCustomerId() !== (int)$customerId) {
                    $this->messageManager->addErrorMessage(__('You are not authorized to withdraw this order.'));
                    return $redirect->setPath('sales/order/history');
                }
            } else {
                if (!$guestEmail || strtolower($guestEmail) !== strtolower($order->getCustomerEmail())) {
                    $this->messageManager->addErrorMessage(__('The provided email does not match the order.'));
                    return $redirect->setPath('withdrawal/guest/search');
                }
            }

            // Check if within withdrawal period
            if (!$this->config->isWithdrawalAllowed($order)) {
                $this->messageManager->addErrorMessage(
                    __('The withdrawal period for this order has expired.')
                );
                if ($isGuest) {
                    return $redirect->setPath('withdrawal/guest/search');
                }
                return $redirect->setPath('sales/order/history');
            }

            // Get withdrawable items (excluding already withdrawn)
            $alreadyWithdrawn = $this->config->getAlreadyWithdrawnItemIds($orderId);
            $withdrawableItems = $this->config->getWithdrawableItems($order, $alreadyWithdrawn);

            if (empty($withdrawableItems)) {
                $this->messageManager->addErrorMessage(
                    __('All items in this order have already been withdrawn.')
                );
                if ($isGuest) {
                    return $redirect->setPath('withdrawal/guest/search');
                }
                return $redirect->setPath('sales/order/history');
            }

            // Get selected item IDs from request
            $selectedItemIds = $this->request->getParam('withdrawal_items', []);
            if (!is_array($selectedItemIds)) {
                $selectedItemIds = [];
            }
            $selectedItemIds = array_map('intval', $selectedItemIds);

            // Validate that selected items are actually withdrawable
            $withdrawableItemIds = array_map(function ($item) {
                return (int)$item->getId();
            }, $withdrawableItems);

            // Filter: only keep selected items that are also withdrawable
            $withdrawableItemIds = array_intersect($selectedItemIds, $withdrawableItemIds);

            if (empty($withdrawableItemIds)) {
                $this->messageManager->addErrorMessage(
                    __('Please select at least one item to withdraw.')
                );
                if ($isGuest) {
                    return $redirect->setPath('withdrawal/index/view', ['order_id' => $orderId, 'guest' => 1]);
                }
                return $redirect->setPath('withdrawal/index/view', ['order_id' => $orderId]);
            }

            // Filter withdrawableItems to only include selected items
            $selectedWithdrawableItems = array_filter($withdrawableItems, function ($item) use ($withdrawableItemIds) {
                return in_array((int)$item->getId(), $withdrawableItemIds, true);
            });

            // Get non-withdrawable items for email
            $nonWithdrawableItems = $this->config->getNonWithdrawableItems($order);
            $totalVisibleItems = count($order->getAllVisibleItems());

            // Build customer name
            $customerName = trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname());
            if (!$customerName || $customerName === ' ') {
                $billingAddress = $order->getBillingAddress();
                if ($billingAddress) {
                    $customerName = trim($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
                }
            }

            // Check if withdrawal already exists for this order
            $existingWithdrawal = $this->withdrawalRepository->getByOrderId($orderId);
            $connection = $this->resource->getConnection();

            if ($existingWithdrawal) {
                // UPDATE existing withdrawal
                $existingItemIds = json_decode($existingWithdrawal->getData('withdrawn_items'), true) ?: [];
                $mergedItemIds = array_unique(array_merge($existingItemIds, $withdrawableItemIds));

                // Determine if this completes the withdrawal
                // Type is 'full' when ALL withdrawable items are withdrawn (non-withdrawable items don't matter)
                $remainingItems = $this->config->getWithdrawableItems($order, $mergedItemIds);
                $withdrawalType = empty($remainingItems) ? 'full' : 'partial';

                $connection->update(
                    'zwernemann_withdrawal',
                    [
                        'withdrawn_items' => json_encode($mergedItemIds),
                        'withdrawn_item_count' => count($mergedItemIds),
                        'withdrawal_type' => $withdrawalType,
                        'updated_at' => $this->dateTime->gmtDate()
                    ],
                    ['entity_id = ?' => $existingWithdrawal->getId()]
                );

                $isUpdate = true;
                $previousItemCount = count($existingItemIds);
            } else {
                // CREATE new withdrawal
                // Get all withdrawable items (excluding non-withdrawable) to determine if this is a full withdrawal
                $allWithdrawableItemIds = array_map(function ($item) {
                    return (int)$item->getId();
                }, $this->config->getWithdrawableItems($order, []));

                // Type is 'full' when we're withdrawing ALL withdrawable items
                // (non-withdrawable items don't count towards partial/full determination)
                $withdrawalType = (count($withdrawableItemIds) === count($allWithdrawableItemIds)) ? 'full' : 'partial';

                $connection->insert('zwernemann_withdrawal', [
                    'order_id' => $order->getEntityId(),
                    'order_increment_id' => $order->getIncrementId(),
                    'customer_email' => $order->getCustomerEmail(),
                    'customer_name' => $customerName,
                    'status' => 'pending',
                    'order_created_at' => $order->getCreatedAt(),
                    'created_at' => $this->dateTime->gmtDate(),
                    'withdrawn_items' => json_encode($withdrawableItemIds),
                    'withdrawal_type' => $withdrawalType,
                    'withdrawn_item_count' => count($withdrawableItemIds)
                ]);

                $isUpdate = false;
                $previousItemCount = 0;
                $mergedItemIds = $withdrawableItemIds;
            }

            // Add comment to order history
            if ($isUpdate) {
                if ($withdrawalType === 'full') {
                    $commentText = __('Withdrawal updated: Now complete withdrawal of all %1 items on %2',
                        count($mergedItemIds),
                        $this->dateTime->gmtDate()
                    );
                } else {
                    $commentText = __('Withdrawal updated: Additional %1 items withdrawn (total %2 of %3) on %4',
                        count($withdrawableItemIds),
                        count($mergedItemIds),
                        $totalVisibleItems,
                        $this->dateTime->gmtDate()
                    );
                }
            } else {
                if ($withdrawalType === 'partial') {
                    $commentText = __('Partial withdrawal: %1 of %2 items withdrawn on %3',
                        count($withdrawableItemIds),
                        $totalVisibleItems,
                        $this->dateTime->gmtDate()
                    );
                } else {
                    $commentText = __('Full withdrawal submitted on %1', $this->dateTime->gmtDate());
                }
            }

            $order->addCommentToStatusHistory($commentText);
            $this->orderRepository->save($order);

            // Send emails
            $templateVars = [
                'order_increment_id' => $order->getIncrementId(),
                'customer_name' => $customerName,
                'customer_email' => $order->getCustomerEmail(),
                'order_date' => $order->getCreatedAt(),
                'withdrawal_date' => $this->dateTime->gmtDate(),
                'withdrawal_type' => $withdrawalType,
                'withdrawn_item_count' => count($mergedItemIds),
                'newly_withdrawn_item_count' => count($withdrawableItemIds),
                'previous_item_count' => $previousItemCount,
                'total_item_count' => $totalVisibleItems,
                'is_update' => $isUpdate ? '1' : '',
                'is_now_complete' => ($withdrawalType === 'full' && $isUpdate) ? '1' : ''
            ];

            $this->emailSender->sendCustomerEmail(
                $templateVars,
                $order->getCustomerEmail(),
                $customerName,
                $selectedWithdrawableItems,
                $nonWithdrawableItems,
                $withdrawalType,
                $isUpdate
            );
            $this->emailSender->sendAdminEmail(
                $templateVars,
                $selectedWithdrawableItems,
                $nonWithdrawableItems,
                $withdrawalType,
                $isUpdate
            );

            // Redirect to success page
            return $redirect->setPath('withdrawal/index/success', [
                'order_id' => $orderId,
            ]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Unable to submit withdrawal request. Please try again.'));
        }

        if ($isGuest) {
            return $redirect->setPath('withdrawal/guest/search');
        }
        return $redirect->setPath('sales/order/history');
    }
}

