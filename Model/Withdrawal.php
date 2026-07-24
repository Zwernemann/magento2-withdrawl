<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Phrase;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal as WithdrawalResource;

class Withdrawal extends AbstractModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';

    protected function _construct()
    {
        $this->_init(WithdrawalResource::class);
    }

    /**
     * Human-readable label for the current withdrawal status, suitable for
     * display to the customer. Reuses the same wording as the admin view
     * (Pending / Confirmed / Rejected), which is already translated.
     */
    public function getStatusLabel(): Phrase
    {
        switch ((string) $this->getData('status')) {
            case self::STATUS_CONFIRMED:
                return __('Confirmed');
            case self::STATUS_REJECTED:
                return __('Rejected');
            case self::STATUS_PENDING:
                return __('Pending');
            default:
                return __('Withdrawal submitted');
        }
    }
}
