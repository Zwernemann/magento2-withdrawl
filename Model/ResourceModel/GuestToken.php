<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class GuestToken extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('zwernemann_withdrawal_guest_token', 'entity_id');
    }
}

