<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Plugin;

use Zwernemann\Withdrawal\Model\WithdrawalRepository;
use Zwernemann\Withdrawal\Helper\Config;

class RepositoryPlugin
{
    private $config;
    private $injected = [];

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Inject config helper before any method that needs it
     */
    private function injectConfigHelper(WithdrawalRepository $subject): void
    {
        $hash = spl_object_hash($subject);
        if (!isset($this->injected[$hash])) {
            $subject->setConfigHelper($this->config);
            $this->injected[$hash] = true;
        }
    }

    public function beforeHasWithdrawal(WithdrawalRepository $subject)
    {
        $this->injectConfigHelper($subject);
    }

    public function beforeGetWithdrawnItemIds(WithdrawalRepository $subject)
    {
        $this->injectConfigHelper($subject);
    }

    public function beforeGetList(WithdrawalRepository $subject)
    {
        $this->injectConfigHelper($subject);
    }
}

