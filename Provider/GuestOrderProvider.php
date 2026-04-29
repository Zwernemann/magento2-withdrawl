<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Provider;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;

class GuestOrderProvider
{
    public function __construct(private readonly OrderCollectionFactory $orderCollectionFactory) {}

    public function getByIncrementIdAndEmail(string $incrementId, string $email): ?Order
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('increment_id', $incrementId);
        $collection->addFieldToFilter('customer_email', $email);
        $collection->setPageSize(1);

        return $collection->getFirstItem();
    }
}
