<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model\Backpressure;

use Magento\Framework\Webapi\Backpressure\BackpressureRequestTypeExtractorInterface;
use Zwernemann\Withdrawal\Api\WithdrawalConfirmationInterface;

class WebapiRequestTypeExtractor implements BackpressureRequestTypeExtractorInterface
{
    private const WITHDRAWAL_METHODS = ['sendConfirmation', 'canWithdraw'];

    public function __construct(
        private readonly WithdrawalLimitConfigManager $config
    ) {
    }

    public function extract(string $service, string $method, string $endpoint): ?string
    {
        if ($service !== WithdrawalConfirmationInterface::class) {
            return null;
        }
        if (!in_array($method, self::WITHDRAWAL_METHODS, true)) {
            return null;
        }
        return $this->config->isEnforcementEnabled()
            ? WithdrawalLimitConfigManager::REQUEST_TYPE_ID
            : null;
    }
}
