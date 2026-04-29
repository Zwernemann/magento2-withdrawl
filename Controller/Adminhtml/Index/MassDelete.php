<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Ui\Component\MassAction\Filter;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal as WithdrawalResource;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal\CollectionFactory;

class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'Zwernemann_Withdrawal::withdrawals';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly WithdrawalResource $withdrawalResource,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deleted = 0;

        foreach ($collection as $withdrawal) {
            try {
                $this->withdrawalResource->delete($withdrawal);
                $deleted++;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Could not delete withdrawal #%1: %2', $withdrawal->getId(), $e->getMessage())
                );
            }
        }

        if ($deleted) {
            $this->messageManager->addSuccessMessage(__('Deleted %1 withdrawal record(s).', $deleted));
        }

        return $this->resultRedirectFactory->create()->setPath('withdrawal/index/index');
    }
}
