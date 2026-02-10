<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

use Magento\Framework\Model\AbstractModel;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal as WithdrawalResource;

class Withdrawal extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(WithdrawalResource::class);
    }
}
