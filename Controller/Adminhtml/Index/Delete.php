<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal as WithdrawalResource;
use Zwernemann\Withdrawal\Model\WithdrawalFactory;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'Zwernemann_Withdrawal::withdrawals';

    public function __construct(
        Context $context,
        private readonly WithdrawalFactory $withdrawalFactory,
        private readonly WithdrawalResource $withdrawalResource,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setPath('withdrawal/index/index');

        $id = (int) $this->getRequest()->getParam('id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('Invalid withdrawal ID.'));

            return $redirect;
        }

        try {
            $withdrawal = $this->withdrawalFactory->create();
            $this->withdrawalResource->load($withdrawal, $id);

            if (!$withdrawal->getId()) {
                $this->messageManager->addErrorMessage(__('Withdrawal record not found.'));

                return $redirect;
            }

            $this->withdrawalResource->delete($withdrawal);
            $this->messageManager->addSuccessMessage(__('Withdrawal record has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not delete withdrawal record: %1', $e->getMessage()));
        }

        return $redirect;
    }
}
