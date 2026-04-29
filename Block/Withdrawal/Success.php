<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Withdrawal;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Zwernemann\Withdrawal\Model\Session as WithdrawalSession;

class Success extends Template
{
    private $withdrawalSession;

    public function __construct(
        Context $context,
        WithdrawalSession $withdrawalSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->withdrawalSession = $withdrawalSession;
    }

    public function getOrder()
    {
        return $this->withdrawalSession->getLastWithdrawnOrder();
    }

    public function getOrderHistoryUrl(): string
    {
        return $this->getUrl('sales/order/history');
    }

    public function getHomeUrl(): string
    {
        return $this->getUrl('/');
    }
}
