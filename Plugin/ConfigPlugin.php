<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Plugin;

use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class ConfigPlugin
{
    private $withdrawalRepository;

    public function __construct(
        WithdrawalRepository $withdrawalRepository
    ) {
        $this->withdrawalRepository = $withdrawalRepository;
    }

    public function afterGetExcludedProductAttributes(Config $subject, $result)
    {
        // Inject repository into config helper after construction
        $subject->setWithdrawalRepository($this->withdrawalRepository);
        return $result;
    }
}

