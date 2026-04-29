<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Index;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Result\PageFactory;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\Session as WithdrawalSession;

class View implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly CustomerSession $customerSession,
        private readonly Config $config,
        private readonly WithdrawalSession $withdrawalSession,
    ) {}

    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        $fallbackRoute = $this->customerSession->isLoggedIn() ? 'sales/order/history' : 'withdrawal/guest/search';

        if (!$this->config->isEnabled()) {
            $this->messageManager->addErrorMessage(__('The withdrawal function is currently not available.'));

            return $redirect->setPath($fallbackRoute);
        }

        $order = $this->withdrawalSession->getWithdrawalOrder();

        if (!$order) {
            return $redirect->setPath($fallbackRoute);
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Withdrawal for Order #%1', $order->getIncrementId()));

        return $page;
    }
}
