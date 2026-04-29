<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Zwernemann\Withdrawal\Model\Session as WithdrawalSession;

class ClearWithdrawalSessionObserver implements ObserverInterface
{
    public function __construct(private readonly WithdrawalSession $withdrawalSession) {}

    public function execute(Observer $observer): void
    {
        $this->withdrawalSession->clearStorage();
    }
}
