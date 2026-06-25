<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zwernemann\Withdrawal\Api\WithdrawalRepositoryInterface;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal as WithdrawalResource;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal\CollectionFactory;
use Zwernemann\Withdrawal\Model\WithdrawalFactory;

class WithdrawalRepository implements WithdrawalRepositoryInterface
{
    private $resource;
    private $withdrawalFactory;
    private $collectionFactory;
    private $resourceConnection;
    private $orderRepository;
    private $dateTime;

    public function __construct(
        WithdrawalResource $resource,
        WithdrawalFactory $withdrawalFactory,
        CollectionFactory $collectionFactory,
        ResourceConnection $resourceConnection,
        OrderRepositoryInterface $orderRepository,
        DateTime $dateTime
    ) {
        $this->resource = $resource;
        $this->withdrawalFactory = $withdrawalFactory;
        $this->collectionFactory = $collectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->orderRepository = $orderRepository;
        $this->dateTime = $dateTime;
    }

    public function create($orderId, $comment = null)
    {
        $withdrawal = $this->withdrawalFactory->create();
        $withdrawal->setData('order_id', $orderId);
        $withdrawal->setData('comment', $comment);
        $this->resource->save($withdrawal);
        return $withdrawal;
    }

    /**
     * Create a withdrawal record from an order with all required fields populated.
     */
    public function createFromOrder(
        \Magento\Sales\Api\Data\OrderInterface $order,
        bool $isPartial = false,
        ?string $comment = null
    ): Withdrawal {
        $customerName = trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname());
        if ($customerName === '' && $order->getBillingAddress()) {
            $billingAddress = $order->getBillingAddress();
            $customerName = trim($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
        }

        $withdrawal = $this->withdrawalFactory->create();
        $withdrawal->setData([
            'order_id' => (int) $order->getEntityId(),
            'order_increment_id' => $order->getIncrementId(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_name' => $customerName,
            'status' => 'pending',
            'is_partial' => $isPartial ? 1 : 0,
            'order_created_at' => $order->getCreatedAt(),
            'created_at' => $this->dateTime->gmtDate(),
            'comment' => $comment,
        ]);
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
        $previousStatus = (string) $withdrawal->getData('status');
        $withdrawal->setData('status', $status);
        $this->resource->save($withdrawal);

        // Record the decision in the order's status history, just like submission is.
        // Only add a comment when the status actually changed to avoid duplicates
        // (e.g. when the same status is applied again via mass action).
        if ($status !== $previousStatus) {
            $this->addOrderHistoryComment($withdrawal, $status);
        }
    }

    /**
     * Adds a status-history comment to the related order when a withdrawal is
     * confirmed or rejected.
     */
    private function addOrderHistoryComment(Withdrawal $withdrawal, string $status): void
    {
        $orderId = (int) $withdrawal->getData('order_id');
        if (!$orderId) {
            return;
        }

        if ($status === 'confirmed') {
            $comment = __('Withdrawal request confirmed by the shop on %1.', $this->dateTime->gmtDate());
        } elseif ($status === 'rejected') {
            $comment = __('Withdrawal request rejected by the shop on %1.', $this->dateTime->gmtDate());
        } else {
            return;
        }

        try {
            $order = $this->orderRepository->get($orderId);
            $order->addCommentToStatusHistory($comment);
            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            // Status update must not fail because of a missing/locked order.
            return;
        }
    }

    /**
     * Save items for a withdrawal request.
     *
     * @param int $withdrawalId
     * @param array $items Each entry: ['order_item_id' => int, 'name' => string, 'sku' => string, 'qty' => float]
     */
    public function saveWithdrawalItems(int $withdrawalId, array $items): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('zwernemann_withdrawal_items');

        foreach ($items as $item) {
            $connection->insert($tableName, [
                'withdrawal_id'   => $withdrawalId,
                'order_item_id'   => (int) $item['order_item_id'],
                'order_item_name' => $item['name'] ?? null,
                'order_item_sku'  => $item['sku'] ?? null,
                'qty_withdrawn'   => (float) ($item['qty'] ?? 1),
            ]);
        }
    }

    /**
     * Returns all order_item_ids that have already been included in any withdrawal for this order.
     *
     * @return int[]
     */
    public function getWithdrawnOrderItemIds(int $orderId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $withdrawalTable = $this->resourceConnection->getTableName('zwernemann_withdrawal');
        $itemsTable = $this->resourceConnection->getTableName('zwernemann_withdrawal_items');

        $select = $connection->select()
            ->from(['wi' => $itemsTable], ['wi.order_item_id'])
            ->join(['w' => $withdrawalTable], 'w.entity_id = wi.withdrawal_id', [])
            ->where('w.order_id = ?', $orderId);

        $result = $connection->fetchCol($select);
        return array_map('intval', $result);
    }

    /**
     * Returns items stored for a given withdrawal record.
     *
     * @return array
     */
    public function getItemsByWithdrawalId(int $withdrawalId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('zwernemann_withdrawal_items');

        $select = $connection->select()
            ->from($tableName)
            ->where('withdrawal_id = ?', $withdrawalId);

        return $connection->fetchAll($select);
    }
}
