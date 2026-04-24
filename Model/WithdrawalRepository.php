<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Zwernemann\Withdrawal\Api\WithdrawalRepositoryInterface;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal as WithdrawalResource;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal\CollectionFactory;
use Zwernemann\Withdrawal\Model\WithdrawalFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class WithdrawalRepository implements WithdrawalRepositoryInterface
{
    protected $resource;
    protected $withdrawalFactory;
    protected $collectionFactory;
    protected $orderRepository;
    protected $configHelper;

    public function __construct(
        WithdrawalResource $resource,
        WithdrawalFactory $withdrawalFactory,
        CollectionFactory $collectionFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->resource = $resource;
        $this->withdrawalFactory = $withdrawalFactory;
        $this->collectionFactory = $collectionFactory;
        $this->orderRepository = $orderRepository;
    }

    public function setConfigHelper($configHelper)
    {
        $this->configHelper = $configHelper;
    }

    public function create($orderId, $comment = null)
    {
        $withdrawal = $this->withdrawalFactory->create();
        $withdrawal->setData('order_id', $orderId);
        $withdrawal->setData('comment', $comment);
        $this->resource->save($withdrawal);
        return $withdrawal;
    }

    public function getList()
    {
        $collection = $this->collectionFactory->create();
        $collection->setOrder('created_at', 'DESC');

        $result = [];
        foreach ($collection as $item) {
            $data = $item->getData();

            // Decode withdrawn_items JSON
            if (!empty($data['withdrawn_items'])) {
                try {
                    $data['withdrawn_items'] = json_decode($data['withdrawn_items'], true);
                } catch (\Exception $e) {
                    $data['withdrawn_items'] = [];
                }
            } else {
                $data['withdrawn_items'] = [];
            }

            $result[] = $data;
        }

        return $result;
    }

    public function getByOrderId(int $orderId): ?Withdrawal
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('order_id', $orderId);
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();
        if ($item && $item->getId()) {
            return $item;
        }
        return null;
    }

    /**
     * Get all withdrawals by order ID (returns array with one or zero elements for single-entry approach)
     */
    public function getAllWithdrawalsByOrderId(int $orderId): array
    {
        $withdrawal = $this->getByOrderId($orderId);
        return $withdrawal ? [$withdrawal] : [];
    }

    /**
     * Get all withdrawn item IDs for an order
     */
    public function getWithdrawnItemIds(int $orderId): array
    {
        $withdrawal = $this->getByOrderId($orderId);

        if (!$withdrawal) {
            return [];
        }

        $withdrawnItems = $withdrawal->getData('withdrawn_items');
        if (empty($withdrawnItems)) {
            return [];
        }

        try {
            $itemIds = json_decode($withdrawnItems, true);
            return is_array($itemIds) ? array_unique(array_filter($itemIds)) : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function hasWithdrawal(int $orderId): bool
    {
        $withdrawal = $this->getByOrderId($orderId);

        if (!$withdrawal) {
            return false;
        }

        // Check if all withdrawable items have been withdrawn
        if ($this->configHelper) {
            try {
                $order = $this->orderRepository->get($orderId);
                $withdrawnItemIds = $this->getWithdrawnItemIds($orderId);

                // Get withdrawable items EXCLUDING already withdrawn ones
                $withdrawableItems = $this->configHelper->getWithdrawableItems($order, $withdrawnItemIds);

                // If there are still withdrawable items left, return false
                if (!empty($withdrawableItems)) {
                    return false; // Still items available to withdraw
                }

                return true; // All withdrawable items withdrawn
            } catch (\Exception $e) {
                // Fallback to old logic
                return true;
            }
        }

        return true;
    }

    public function getById(int $entityId): Withdrawal
    {
        $withdrawal = $this->withdrawalFactory->create();
        $this->resource->load($withdrawal, $entityId);
        if (!$withdrawal->getId()) {
            throw new NoSuchEntityException(__('Withdrawal with ID "%1" does not exist.', $entityId));
        }
        return $withdrawal;
    }

    public function updateStatus(int $entityId, string $status): void
    {
        $withdrawal = $this->getById($entityId);
        $withdrawal->setData('status', $status);
        $this->resource->save($withdrawal);
    }
}

