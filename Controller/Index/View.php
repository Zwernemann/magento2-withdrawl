<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class View implements HttpGetActionInterface
{
    private $request;
    private $pageFactory;
    private $redirectFactory;
    private $messageManager;
    private $orderRepository;
    private $customerSession;
    private $config;
    private $withdrawalRepository;

    public function __construct(
        RequestInterface $request,
        PageFactory $pageFactory,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        CustomerSession $customerSession,
        Config $config,
        WithdrawalRepository $withdrawalRepository
    ) {
        $this->request = $request;
        $this->pageFactory = $pageFactory;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
        $this->config = $config;
        $this->withdrawalRepository = $withdrawalRepository;
    }

    public function execute()
    {
        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('The withdrawal function is currently not available.'));
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('sales/order/history');
        }

        $orderId = (int) $this->request->getParam('order_id');
        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('No order specified.'));
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('sales/order/history');
        }

        // Only for logged in customers
        if (!$this->customerSession->isLoggedIn()) {
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('customer/account/login');
        }

        try {
            $order = $this->orderRepository->get($orderId);
            $customerId = $this->customerSession->getCustomerId();

            if ((int) $order->getCustomerId() !== (int) $customerId) {
                $this->messageManager->addErrorMessage(__('You are not authorized to view this order.'));
                $redirect = $this->redirectFactory->create();
                return $redirect->setPath('sales/order/history');
            }

            $page = $this->pageFactory->create();
            $page->getConfig()->getTitle()->set(
                __('Withdrawal for Order #%1', $order->getIncrementId())
            );
            return $page;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('The order could not be found.'));
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('sales/order/history');
        }
    }
}
