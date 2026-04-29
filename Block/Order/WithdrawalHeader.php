<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Zwernemann\Withdrawal\Helper\Config;

class WithdrawalHeader extends Template
{
    public function __construct(
        private readonly Config $config,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function isEnabled(): bool
    {
        return $this->config->isEnabled();
    }
}
