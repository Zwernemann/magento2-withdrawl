<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model\Backpressure;

use Magento\Framework\App\Backpressure\ContextInterface;
use Magento\Framework\App\Backpressure\SlidingWindow\LimitConfig;
use Magento\Framework\App\Backpressure\SlidingWindow\LimitConfigManagerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\RuntimeException;
use Magento\Store\Model\ScopeInterface;

class WithdrawalLimitConfigManager implements LimitConfigManagerInterface
{
    public const REQUEST_TYPE_ID = 'withdrawal-api';

    private const XML_PATH_API_ENABLED = 'zwernemann_withdrawal/api/enabled';
    private const XML_PATH_ENABLED = 'zwernemann_withdrawal/api/backpressure_enabled';
    private const XML_PATH_LIMIT = 'zwernemann_withdrawal/api/backpressure_limit';
    private const XML_PATH_PERIOD = 'zwernemann_withdrawal/api/backpressure_period';

    public function __construct(
        private readonly ScopeConfigInterface $config
    ) {
    }

    public function readLimit(ContextInterface $context): LimitConfig
    {
        $limit = (int) $this->config->getValue(self::XML_PATH_LIMIT, ScopeInterface::SCOPE_STORE);
        $period = (int) $this->config->getValue(self::XML_PATH_PERIOD, ScopeInterface::SCOPE_STORE);
        if ($limit <= 0 || $period <= 0) {
            throw new RuntimeException(__('Withdrawal backpressure limit and period must be positive.'));
        }
        return new LimitConfig($limit, $period);
    }

    public function isEnforcementEnabled(): bool
    {
        if (!$this->config->isSetFlag(self::XML_PATH_API_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return false;
        }

        return $this->config->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }
}
