<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Customer\Model\Session as CustomerSession;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;
use Zwernemann\Withdrawal\Model\Email\Sender as EmailSender;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;

class Submit implements HttpPostActionInterface
{
    private $request;
    private $redirectFactory;
    private $messageManager;
    private $orderRepository;
    private $dateTime;
    private $customerSession;
    private $config;
    private $withdrawalRepository;
    private $emailSender;
    private $resource;
    private $formKeyValidator;

    public function __construct(
        RequestInterface $request,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        DateTime $dateTime,
        CustomerSession $customerSession,
        Config $config,
        WithdrawalRepository $withdrawalRepository,
        EmailSender $emailSender,
        ResourceConnection $resource,
        FormKeyValidator $formKeyValidator
    ) {
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

        $orderId = (int) $this->request->getParam('order_id');
        $isGuest = (bool) $this->request->getParam('guest');
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
                if ((int) $order->getCustomerId() !== (int) $customerId) {
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

            // Build a map of visible order items keyed by item_id
            $orderItemsById = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $orderItemsById[(int) $item->getItemId()] = $item;
            }

            $partialAllowed = $this->config->isPartialWithdrawalAllowed();

            // Resolve selected items from POST
            // selected_items[] contains order_item_ids; item_qty[<id>] contains the qty to withdraw
            $selectedItemIds = array_map('intval', (array) $this->request->getParam('selected_items', []));
            $itemQtyMap = (array) $this->request->getParam('item_qty', []);

            if (empty($selectedItemIds)) {
                $this->messageManager->addErrorMessage(
                    __('Please select at least one item to withdraw.')
                );
                return $redirect->setPath('withdrawal/index/view', ['order_id' => $orderId]);
            }

            // If partial withdrawal is disabled, force-select all order items
            if (!$partialAllowed) {
                $selectedItemIds = array_keys($orderItemsById);
                $itemQtyMap = [];
            }

            // Validate that all selected items belong to this order
            foreach ($selectedItemIds as $selectedId) {
                if (!isset($orderItemsById[$selectedId])) {
                    $this->messageManager->addErrorMessage(__('Invalid item selection.'));
                    return $redirect->setPath('withdrawal/index/view', ['order_id' => $orderId]);
                }
            }

            // When partial withdrawal is disabled but a partial selection was posted, reject it
            if (!$partialAllowed && count($selectedItemIds) < count($orderItemsById)) {
                $this->messageManager->addErrorMessage(
                    __('Partial withdrawal is not enabled. Please withdraw the entire order.')
                );
                return $redirect->setPath('withdrawal/index/view', ['order_id' => $orderId]);
            }

            // Check that none of the selected items have already been withdrawn
            $alreadyWithdrawnIds = $this->withdrawalRepository->getWithdrawnOrderItemIds($orderId);
            $alreadyWithdrawnSelected = array_intersect($selectedItemIds, $alreadyWithdrawnIds);
            if (!empty($alreadyWithdrawnSelected)) {
                $this->messageManager->addErrorMessage(
                    __('One or more selected items have already been withdrawn.')
                );
                return $redirect->setPath('withdrawal/index/view', ['order_id' => $orderId]);
            }

            // Determine if this is a partial withdrawal
            $allOrderItemIds = array_keys($orderItemsById);
            $isPartial = count(array_diff($allOrderItemIds, $selectedItemIds)) > 0
                || count(array_diff($alreadyWithdrawnIds, [])) > 0;

            // Prepare item rows to store
            $itemsToSave = [];
            foreach ($selectedItemIds as $itemId) {
                $orderItem = $orderItemsById[$itemId];
                $requestedQty = isset($itemQtyMap[$itemId]) ? (float) $itemQtyMap[$itemId] : null;
                $maxQty = (float) $orderItem->getQtyOrdered();
                $qty = ($requestedQty !== null && $requestedQty > 0 && $requestedQty <= $maxQty)
                    ? $requestedQty
                    : $maxQty;

                $itemsToSave[] = [
                    'order_item_id' => $itemId,
                    'name'          => $orderItem->getName(),
                    'sku'           => $orderItem->getSku(),
                    'qty'           => $qty,
                ];
            }

            // Build customer name
            $customerName = trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname());
            if (!$customerName || $customerName === ' ') {
                $billingAddress = $order->getBillingAddress();
                if ($billingAddress) {
                    $customerName = trim($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
                }
            }

            // Create withdrawal record
            $connection = $this->resource->getConnection();
            $connection->insert('zwernemann_withdrawal', [
                'order_id'          => $order->getEntityId(),
                'order_increment_id' => $order->getIncrementId(),
                'customer_email'    => $order->getCustomerEmail(),
                'customer_name'     => $customerName,
                'status'            => 'pending',
                'is_partial'        => $isPartial ? 1 : 0,
                'order_created_at'  => $order->getCreatedAt(),
                'created_at'        => $this->dateTime->gmtDate(),
            ]);

            $withdrawalId = (int) $connection->lastInsertId();

            // Save selected items
            $this->withdrawalRepository->saveWithdrawalItems($withdrawalId, $itemsToSave);

            // Build order comment
            if ($isPartial) {
                $itemNames = array_column($itemsToSave, 'name');
                $orderComment = __(
                    'Partial withdrawal requested by customer on %1. Items: %2',
                    $this->dateTime->gmtDate(),
                    implode(', ', $itemNames)
                );
            } else {
                $orderComment = __('Withdrawal requested by customer on %1.', $this->dateTime->gmtDate());
            }

            $order->addCommentToStatusHistory($orderComment);
            $this->orderRepository->save($order);

            // Prepare email variables including item list
            $itemLines = [];
            foreach ($itemsToSave as $savedItem) {
                $itemLines[] = sprintf('%s (SKU: %s) x %s', $savedItem['name'], $savedItem['sku'], (int) $savedItem['qty']);
            }

            $templateVars = [
                'order_increment_id'    => $order->getIncrementId(),
                'customer_name'         => $customerName,
                'customer_email'        => $order->getCustomerEmail(),
                'order_date'            => $order->getCreatedAt(),
                'withdrawal_date'       => $this->dateTime->gmtDate(),
                'withdrawal_type_label' => $isPartial
                    ? (string) __('partial withdrawal')
                    : (string) __('withdrawal'),
                'withdrawn_items'       => implode("\n", $itemLines),
            ];

            $this->emailSender->sendCustomerEmail(
                $templateVars,
                $order->getCustomerEmail(),
                $customerName
            );
            $this->emailSender->sendAdminEmail($templateVars);

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
