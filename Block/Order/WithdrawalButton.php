<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\Withdrawal;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class WithdrawalButton extends Template
{
    private $config;
    private $withdrawalRepository;

    /** @var array<int, Withdrawal|null> */
    private $withdrawalCache = [];

    public function __construct(
        Context $context,
        Config $config,
        WithdrawalRepository $withdrawalRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->withdrawalRepository = $withdrawalRepository;
    }

    public function getViewUrl(int $orderId): string
    {
        return $this->getUrl('withdrawal/index/view', ['order_id' => $orderId]);
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }

    public function isWithdrawalAllowed($order): bool
    {
        return $this->config->isWithdrawalAllowed($order);
    }

    public function hasWithdrawal(int $orderId): bool
    {
        return $this->getWithdrawal($orderId) !== null;
    }

    /**
     * Returns the withdrawal record for the given order, or null if none.
     * Result is cached per order so repeated calls in a template row do not
     * trigger additional queries.
     */
    public function getWithdrawal(int $orderId): ?Withdrawal
    {
        if (!array_key_exists($orderId, $this->withdrawalCache)) {
            $this->withdrawalCache[$orderId] = $this->withdrawalRepository->getByOrderId($orderId);
        }
        return $this->withdrawalCache[$orderId];
    }
}
