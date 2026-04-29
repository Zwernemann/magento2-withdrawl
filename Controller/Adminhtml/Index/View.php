<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class View extends Action
{
    const ADMIN_RESOURCE = 'Zwernemann_Withdrawal::withdrawals';

    private $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $id = (int) $this->getRequest()->getParam('id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('Invalid withdrawal request.'));
            return $this->resultRedirectFactory->create()->setPath('withdrawal/index/index');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Zwernemann_Withdrawal::withdrawals');
        $resultPage->getConfig()->getTitle()->prepend(__('Withdrawal Request #%1', $id));
        return $resultPage;
    }
}
