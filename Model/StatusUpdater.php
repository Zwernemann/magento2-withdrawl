<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

/**
 * Updates withdrawal status based on order/creditmemo state
 */
class StatusUpdater
{
    public function __construct(
        protected readonly WithdrawalRepository $withdrawalRepository,
        protected readonly OrderRepositoryInterface $orderRepository,
        protected readonly CreditmemoRepositoryInterface $creditmemoRepository,
        protected readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        protected readonly LoggerInterface $logger
    ) {}

    /**
     * Check if all withdrawn items are credited and update status to 'confirmed'
     *
     * @param int $orderId
     * @return bool True if status was updated
     */
    public function updateStatusIfFullyCredited(int $orderId): bool
    {
        try {
            $withdrawal = $this->withdrawalRepository->getByOrderId($orderId);
            if (!$withdrawal || !$withdrawal->getId()) {
                return false;
            }

            // Only update if status is pending
            if ($withdrawal->getData('status') !== 'pending') {
                return false;
            }

            // Get withdrawn item IDs
            $withdrawnItemsJson = $withdrawal->getData('withdrawn_items');
            if (empty($withdrawnItemsJson)) {
                return false;
            }

            $withdrawnItemIds = json_decode($withdrawnItemsJson, true);
            if (!is_array($withdrawnItemIds) || empty($withdrawnItemIds)) {
                return false;
            }

            // Get order
            $order = $this->orderRepository->get($orderId);

            // Get all credit memos for this order
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('order_id', $orderId)
                ->create();
            $creditmemos = $this->creditmemoRepository->getList($searchCriteria)->getItems();

            // Collect all credited item IDs
            $creditedItemIds = [];
            foreach ($creditmemos as $creditmemo) {
                foreach ($creditmemo->getItems() as $creditmemoItem) {
                    $orderItemId = (int) $creditmemoItem->getOrderItemId();
                    if ($orderItemId > 0 && $creditmemoItem->getQty() > 0) {
                        $creditedItemIds[] = $orderItemId;
                    }
                }
            }
            $creditedItemIds = array_unique($creditedItemIds);

            // Check if all withdrawn items are in creditmemos OR deleted from order
            $allProcessed = true;
            foreach ($withdrawnItemIds as $withdrawnItemId) {
                // Check if item is credited
                if (in_array($withdrawnItemId, $creditedItemIds, true)) {
                    continue;
                }

                // Check if item still exists in order
                $itemStillExists = false;
                foreach ($order->getAllItems() as $orderItem) {
                    if ((int) $orderItem->getId() === (int) $withdrawnItemId) {
                        $itemStillExists = true;
                        break;
                    }
                }

                // If item exists and is not credited, not all items are processed
                if ($itemStillExists) {
                    $allProcessed = false;
                    break;
                }
            }

            // Update status to confirmed if all items are processed
            if ($allProcessed) {
                $this->withdrawalRepository->updateStatus($withdrawal->getId(), 'confirmed');

                // Add comment to order
                $order->addCommentToStatusHistory(
                    __('Withdrawal confirmed: All withdrawn items have been credited or removed.')
                );
                $this->orderRepository->save($order);

                $this->logger->info("Withdrawal #{$withdrawal->getId()} for Order #{$order->getIncrementId()} automatically confirmed.");
                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->logger->error('Error in StatusUpdater::updateStatusIfFullyCredited: ' . $e->getMessage());
            return false;
        }
    }
}

