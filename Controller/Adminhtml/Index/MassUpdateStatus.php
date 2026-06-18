<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Zwernemann\Withdrawal\Api\WithdrawalRepositoryInterface;
use Zwernemann\Withdrawal\Model\Email\Sender;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal\CollectionFactory;

class MassUpdateStatus extends Action
{
    const ADMIN_RESOURCE = 'Zwernemann_Withdrawal::withdrawals';

    private const ALLOWED_STATUSES = ['pending', 'confirmed', 'rejected'];

    private $filter;
    private $collectionFactory;
    private $withdrawalRepository;
    private Sender $emailSender;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        WithdrawalRepositoryInterface $withdrawalRepository,
        Sender $emailSender
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->withdrawalRepository = $withdrawalRepository;
        $this->emailSender = $emailSender;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $status = (string) $this->getRequest()->getParam('status');

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            $this->messageManager->addErrorMessage(__('Invalid status.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $count = 0;
            foreach ($collection as $withdrawal) {

                if ($withdrawal->getStatus() === $status) {
                    continue;
                }

                $this->withdrawalRepository->updateStatus(
                    (int)$withdrawal->getId(),
                    $status
                );

                $templateVars = [
                    'customer_name'      => $withdrawal->getCustomerName(),
                    'order_increment_id' => $withdrawal->getOrderIncrementId(),
                    'is_confirmed'       => $status === 'confirmed' ? '1' : '',
                    'is_rejected'        => $status === 'rejected' ? '1' : ''
                ];

                $this->emailSender->sendStatusUpdateEmail(
                    $templateVars,
                    $withdrawal->getCustomerEmail(),
                    $withdrawal->getCustomerName()
                );

                $count++;
            }
            $this->messageManager->addSuccessMessage(
                __('A total of %1 withdrawal(s) have been updated to "%2".', $count, $status)
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not update withdrawal statuses.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
