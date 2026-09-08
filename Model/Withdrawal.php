<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal as WithdrawalResource;

class Withdrawal extends AbstractModel
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_REJECTED = 'rejected';

    private NameComparison $nameComparison;

    public function __construct(
        Context $context,
        Registry $registry,
        NameComparison $nameComparison,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->nameComparison = $nameComparison;
    }

    protected function _construct()
    {
        $this->_init(WithdrawalResource::class);
    }

    /**
     * Name the consumer declared on the withdrawal form, as required by
     * section 356a BGB / Art. 11a of Directive 2011/83/EU. Empty for records
     * created before the name fields existed or via an API call without a name.
     */
    public function getWithdrawalName(): string
    {
        return trim(
            (string) $this->getData('withdrawal_firstname')
            . ' '
            . (string) $this->getData('withdrawal_lastname')
        );
    }

    /**
     * True when the declared name differs from the name on the order. Spelling
     * variants (case, extra spaces, hyphens, order of the name parts) are
     * ignored, so only a real difference is flagged. The withdrawal stays valid
     * either way; the merchant decides how to handle the discrepancy.
     */
    public function hasNameMismatch(): bool
    {
        $declared = $this->getWithdrawalName();
        $orderName = (string) $this->getData('customer_name');

        if ($declared === '' || trim($orderName) === '') {
            return false;
        }

        return !$this->nameComparison->isSame($declared, $orderName);
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
