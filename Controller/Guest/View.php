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

class View implements HttpGetActionInterface
{
    private $request;
    private $pageFactory;
    private $redirectFactory;
    private $messageManager;
    private $orderRepository;
    private $config;

    public function __construct(
        RequestInterface $request,
        PageFactory $pageFactory,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        Config $config
    ) {
        $this->request = $request;
        $this->pageFactory = $pageFactory;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
    }

    public function execute()
    {
        $redirect = $this->redirectFactory->create();

        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('The withdrawal function is currently not available.'));
            return $redirect->setPath('/');
        }

        $orderId = (int) $this->request->getParam('order_id');
        $email = urldecode((string) $this->request->getParam('email'));

        if (!$orderId || !$email) {
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $redirect->setPath('withdrawal/guest/search');
        }

        try {
            $order = $this->orderRepository->get($orderId);

            if (strtolower($order->getCustomerEmail()) !== strtolower($email)) {
                $this->messageManager->addErrorMessage(__('The provided email does not match the order.'));
                return $redirect->setPath('withdrawal/guest/search');
            }

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
