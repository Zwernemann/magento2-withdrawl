<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Zwernemann\Withdrawal\Api\WithdrawalRepositoryInterface;
use Zwernemann\Withdrawal\Model\Email\Sender;

class UpdateStatus extends Action
{
    const ADMIN_RESOURCE = 'Zwernemann_Withdrawal::withdrawals';

    private const ALLOWED_STATUSES = ['pending', 'confirmed', 'rejected'];

    private $withdrawalRepository;
    private Sender $emailSender;

    public function __construct(
        Context $context,
        WithdrawalRepositoryInterface $withdrawalRepository,
        Sender $emailSender
    ) {
        parent::__construct($context);
        $this->withdrawalRepository = $withdrawalRepository;
        $this->emailSender = $emailSender;
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = (int) $this->getRequest()->getParam('id');
        $status = (string) $this->getRequest()->getParam('status');

        if (!$id || !in_array($status, self::ALLOWED_STATUSES, true)) {
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $resultRedirect->setPath('*/*/');
        }
        $withdrawal = $this->withdrawalRepository->getById($id);

        $this->withdrawalRepository->updateStatus($id, $status);

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

        try {
            $this->withdrawalRepository->updateStatus($id, $status);
            $this->messageManager->addSuccessMessage(__('Withdrawal status has been updated to "%1".', $status));
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not update the withdrawal status.'));
        }

        return $resultRedirect->setPath('*/*/');
    }
}
