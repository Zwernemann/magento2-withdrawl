<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\Session as WithdrawalSession;

class Find implements HttpGetActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CustomerSession $customerSession,
        private readonly Config $config,
        private readonly WithdrawalSession $withdrawalSession,
    ) {}

    public function execute()
    {
        $redirect = $this->redirectFactory->create();

        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('The withdrawal function is currently not available.'));

            return $redirect->setPath('sales/order/history');
        }

        $orderId = (int) $this->request->getParam('order_id');
        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('No order specified.'));

            return $redirect->setPath('sales/order/history');
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $redirect->setPath('customer/account/login');
        }

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException) {
            return $redirect->setPath('sales/order/history');
        }

        if ((int) $order->getCustomerId() !== (int) $this->customerSession->getCustomerId()) {
            return $redirect->setPath('sales/order/history');
        }

        $this->withdrawalSession->setWithdrawalOrderId($orderId);

        return $redirect->setPath('withdrawal/index/view');
    }
}
