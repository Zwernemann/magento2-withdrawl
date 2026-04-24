<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Block to display withdrawal status in order history
 */
class WithdrawalStatus extends Template
{
    public function __construct(
        Context $context,
        protected readonly WithdrawalRepository $withdrawalRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get withdrawal for current order
     *
     * @return \Zwernemann\Withdrawal\Model\Withdrawal|null
     */
    public function getWithdrawal(): ?\Zwernemann\Withdrawal\Model\Withdrawal
    {
        $order = $this->getOrder();
        if (!$order || !$order->getId()) {
            return null;
        }

        return $this->withdrawalRepository->getByOrderId((int) $order->getId());
    }

    /**
     * Get current order from parent block
     *
     * @return OrderInterface|null
     */
    public function getOrder(): ?OrderInterface
    {
        return $this->getParentBlock()?->getOrder();
    }

    /**
     * Get withdrawal status label
     *
     * @param string $status
     * @return string
     */
    public function getStatusLabel(string $status): string
    {
        $labels = [
            'pending' => __('Pending'),
            'confirmed' => __('Confirmed'),
            'approved' => __('Approved'),
            'rejected' => __('Rejected'),
            'completed' => __('Completed')
        ];

        return (string) ($labels[$status] ?? __('Unknown'));
    }

    /**
     * Get CSS class for status badge
     *
     * @param string $status
     * @return string
     */
    public function getStatusClass(string $status): string
    {
        $classes = [
            'pending' => 'warning',
            'confirmed' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'completed' => 'success'
        ];

        return $classes[$status] ?? 'secondary';
    }

    /**
     * Check if withdrawal info should be displayed
     *
     * @return bool
     */
    public function shouldDisplay(): bool
    {
        return $this->getWithdrawal() !== null;
    }
}

