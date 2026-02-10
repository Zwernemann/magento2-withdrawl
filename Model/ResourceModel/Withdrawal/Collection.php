<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Zwernemann\Withdrawal\Model\Withdrawal as WithdrawalModel;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal as WithdrawalResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(WithdrawalModel::class, WithdrawalResource::class);
    }
}
