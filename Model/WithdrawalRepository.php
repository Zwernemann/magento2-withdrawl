<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Zwernemann\Withdrawal\Api\WithdrawalRepositoryInterface;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal as WithdrawalResource;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal\CollectionFactory;
use Zwernemann\Withdrawal\Model\WithdrawalFactory;

class WithdrawalRepository implements WithdrawalRepositoryInterface
{
    private $resource;
    private $withdrawalFactory;
    private $collectionFactory;

    public function __construct(
        WithdrawalResource $resource,
        WithdrawalFactory $withdrawalFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->withdrawalFactory = $withdrawalFactory;
        $this->collectionFactory = $collectionFactory;
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
        return $collection->getData();
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

    public function hasWithdrawal(int $orderId): bool
    {
        return $this->getByOrderId($orderId) !== null;
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
