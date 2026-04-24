<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Guest;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Magento\Customer\Model\Session as CustomerSession;

class View implements HttpGetActionInterface
{
    private $request;
    private $pageFactory;
    private $redirectFactory;
    private $messageManager;
    private $orderRepository;
    private $config;
    protected CustomerSession $customerSession;

    public function __construct(
        RequestInterface $request,
        PageFactory $pageFactory,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        Config $config,
        CustomerSession $customerSession
    ) {
        $this->request = $request;
        $this->pageFactory = $pageFactory;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();

        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('The withdrawal function is currently not available.'));
            return $redirect->setPath('/');
        }

        $orderId = (int) $this->request->getParam('order_id');

        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $redirect->setPath('withdrawal/guest/search');
        }

        // Validate guest token from session
        $sessionToken = $this->customerSession->getGuestWithdrawalToken();
        $sessionOrderId = $this->customerSession->getGuestWithdrawalOrderId();
        $sessionEmail = $this->customerSession->getGuestWithdrawalEmail();

        if (!$sessionToken || $sessionOrderId != $orderId) {
            $this->messageManager->addErrorMessage(
                __('Please use the access link from your email to view the withdrawal form.')
            );
            return $redirect->setPath('withdrawal/guest/search');
        }

        // Validate session data matches order
        try {
            $order = $this->orderRepository->get($orderId);

            if (strtolower($order->getCustomerEmail()) !== strtolower($sessionEmail)) {
                $this->messageManager->addErrorMessage(__('Invalid access.'));
                return $redirect->setPath('withdrawal/guest/search');
            }

            // Guest access validated, show page
            $page = $this->pageFactory->create();
            $page->getConfig()->getTitle()->set(
                __('Withdrawal for Order #%1', $order->getIncrementId())
            );
            return $page;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('The order could not be found.'));
            return $redirect->setPath('withdrawal/guest/search');
        }
    }
}
