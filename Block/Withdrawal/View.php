<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Withdrawal;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class View extends Template
{
    private $request;
    private $orderRepository;
    private $config;
    private $withdrawalRepository;
    private $order;

    public function __construct(
        Context $context,
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        Config $config,
        WithdrawalRepository $withdrawalRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->withdrawalRepository = $withdrawalRepository;
    }

    public function getOrder()
    {
        if ($this->order === null) {
            $orderId = (int) $this->request->getParam('order_id');
            if ($orderId) {
                try {
                    $this->order = $this->orderRepository->get($orderId);
                } catch (\Exception $e) {
                    $this->order = false;
                }
            }
        }
        return $this->order ?: null;
    }

    public function getSubmitUrl(): string
    {
        return $this->getUrl('withdrawal/index/submit');
    }

    public function isWithdrawalAllowed(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }
        return $this->config->isWithdrawalAllowed($order);
    }

    public function hasExistingWithdrawal(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }
        return $this->withdrawalRepository->hasWithdrawal((int) $order->getEntityId());
    }

    public function getWithdrawalDeadline(): string
    {
        $order = $this->getOrder();
        if (!$order) {
            return '';
        }
        return $this->config->getWithdrawalDeadline($order);
    }

    public function isGuest(): bool
    {
        return (bool) $this->request->getParam('email');
    }

    public function getGuestEmail(): string
    {
        return urldecode((string) $this->request->getParam('email'));
    }

    public function getFormattedDate(string $date): string
    {
        try {
            $dateObj = new \DateTime($date);
            return $dateObj->format('d.m.Y H:i');
        } catch (\Exception $e) {
            return $date;
        }
    }
}
