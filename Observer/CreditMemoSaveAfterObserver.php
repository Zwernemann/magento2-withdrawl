<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;
use Zwernemann\Withdrawal\Model\StatusUpdater;
use Psr\Log\LoggerInterface;

/**
 * Observer to check if withdrawal should be confirmed after credit memo creation
 */
class CreditMemoSaveAfterObserver implements ObserverInterface
{
    public function __construct(
        protected readonly WithdrawalRepository $withdrawalRepository,
        protected readonly StatusUpdater $statusUpdater,
        protected readonly LoggerInterface $logger
    ) {}

    /**
     * Check if all withdrawn items are credited and update withdrawal status
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            /** @var CreditmemoInterface $creditmemo */
            $creditmemo = $observer->getEvent()->getCreditmemo();
            $orderId = (int) $creditmemo->getOrderId();

            if (!$orderId) {
                return;
            }

            // Check if withdrawal exists for this order
            $withdrawal = $this->withdrawalRepository->getByOrderId($orderId);
            if (!$withdrawal || !$withdrawal->getId()) {
                return;
            }

            // Only update if status is still pending
            if ($withdrawal->getData('status') !== 'pending') {
                return;
            }

            // Check if all withdrawn items are now credited
            $this->statusUpdater->updateStatusIfFullyCredited($orderId);

        } catch (\Exception $e) {
            $this->logger->error('Error in CreditMemoSaveAfterObserver: ' . $e->getMessage());
        }
    }
}

