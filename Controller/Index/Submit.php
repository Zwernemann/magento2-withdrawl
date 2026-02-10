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

            // Check if already withdrawn
            if ($this->withdrawalRepository->hasWithdrawal($orderId)) {
                $this->messageManager->addErrorMessage(__('A withdrawal request already exists for this order.'));
                if ($isGuest) {
                    return $redirect->setPath('withdrawal/guest/search');
                }
                return $redirect->setPath('sales/order/history');
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
                'order_id' => $order->getEntityId(),
                'order_increment_id' => $order->getIncrementId(),
                'customer_email' => $order->getCustomerEmail(),
                'customer_name' => $customerName,
                'status' => 'pending',
                'order_created_at' => $order->getCreatedAt(),
                'created_at' => $this->dateTime->gmtDate(),
            ]);

            // Add comment to order history
            $order->addCommentToStatusHistory(
                __('Withdrawal requested by customer on %1.', $this->dateTime->gmtDate())
            );
            $this->orderRepository->save($order);

            // Send emails
            $templateVars = [
                'order_increment_id' => $order->getIncrementId(),
                'customer_name' => $customerName,
                'customer_email' => $order->getCustomerEmail(),
                'order_date' => $order->getCreatedAt(),
                'withdrawal_date' => $this->dateTime->gmtDate(),
            ];

            $this->emailSender->sendCustomerEmail(
                $templateVars,
                $order->getCustomerEmail(),
                $customerName
            );
            $this->emailSender->sendAdminEmail($templateVars);

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
