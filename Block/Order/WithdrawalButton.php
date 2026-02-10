<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class WithdrawalButton extends Template
{
    private $config;
    private $withdrawalRepository;

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
        return $this->withdrawalRepository->hasWithdrawal($orderId);
    }
}
