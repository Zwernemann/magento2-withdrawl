<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model\ResourceModel\GuestToken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            \Zwernemann\Withdrawal\Model\GuestToken::class,
            \Zwernemann\Withdrawal\Model\ResourceModel\GuestToken::class
        );
    }
}

