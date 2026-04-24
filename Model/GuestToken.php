<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

use Magento\Framework\Model\AbstractModel;

class GuestToken extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\Zwernemann\Withdrawal\Model\ResourceModel\GuestToken::class);
    }
}

