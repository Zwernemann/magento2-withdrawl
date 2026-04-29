<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Zwernemann\Withdrawal\Model\Session as WithdrawalSession;

class Success implements HttpGetActionInterface
{
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly RedirectFactory $redirectFactory,
        private readonly WithdrawalSession $withdrawalSession,
    ) {}

    public function execute()
    {
        if (!$this->withdrawalSession->getLastWithdrawnOrderId()) {
            return $this->redirectFactory->create()->setPath('/');
        }

        $page = $this->pageFactory->create();
        $page->getConfig()->getTitle()->set(__('Withdrawal Submitted Successfully'));

        return $page;
    }
}
