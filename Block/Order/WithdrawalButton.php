<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class WithdrawalButton extends Template
{
    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var WithdrawalRepository
     */
    protected WithdrawalRepository $withdrawalRepository;

    /**
     * @param Context $context
     * @param Config $config
     * @param WithdrawalRepository $withdrawalRepository
     * @param array $data
     */
    public function __construct(
        Context              $context,
        Config               $config,
        WithdrawalRepository $withdrawalRepository,
        array                $data = []
    )
    {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->withdrawalRepository = $withdrawalRepository;
    }

    /**
     * @param int $orderId
     * @return string
     */
    public function getViewUrl(int $orderId): string
    {
        return $this->getUrl('withdrawal/index/view', ['order_id' => $orderId]);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    /**
     * @param $order
     * @return bool
     */
    public function isWithdrawalAllowed($order): bool
    {
        return $this->config->isWithdrawalAllowed($order);
    }

    /**
     * Get withdrawal for an order
     */
    public function getWithdrawal(int $orderId): ?\Zwernemann\Withdrawal\Model\Withdrawal
    {
        return $this->withdrawalRepository->getByOrderId($orderId);
    }

    /**
     * Get translated status label
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
        return isset($labels[$status]) ? $labels[$status]->render() : __('Unknown')->render();
    }

    /**
     * @param $order
     * @return string
     */
    public function getButtonText($order): string
    {
        $orderId = (int)$order->getEntityId();
        $withdrawals = $this->withdrawalRepository->getAllWithdrawalsByOrderId($orderId);

        if (!empty($withdrawals)) {
            // Check if partial withdrawal exists
            $hasPartial = false;
            foreach ($withdrawals as $withdrawal) {
                if ($withdrawal->getData('withdrawal_type') === 'partial') {
                    $hasPartial = true;
                    break;
                }
            }

            // If withdrawals exist and not fully completed, show "Withdraw More Items"
            if (!$this->hasWithdrawal($orderId)) {
                return __('Withdraw More Items')->render();
            }
        }

        return __('Withdraw Order')->render();
    }

    /**
     * @param int $orderId
     * @return bool
     */
    public function hasWithdrawal(int $orderId): bool
    {
        return $this->withdrawalRepository->hasWithdrawal($orderId);
    }
}
