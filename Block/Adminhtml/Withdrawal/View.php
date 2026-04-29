<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Adminhtml\Withdrawal;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class View extends Template
{
    private $withdrawalRepository;
    private $orderRepository;

    /** @var \Zwernemann\Withdrawal\Model\Withdrawal|null */
    private $withdrawal = null;

    public function __construct(
        Context $context,
        WithdrawalRepository $withdrawalRepository,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->withdrawalRepository = $withdrawalRepository;
        $this->orderRepository = $orderRepository;
    }

    public function getWithdrawal()
    {
        if ($this->withdrawal === null) {
            $id = (int) $this->getRequest()->getParam('id');
            try {
                $this->withdrawal = $this->withdrawalRepository->getById($id);
            } catch (\Exception $e) {
                $this->withdrawal = false;
            }
        }
        return $this->withdrawal ?: null;
    }

    public function getWithdrawalItems(): array
    {
        $withdrawal = $this->getWithdrawal();
        if (!$withdrawal) {
            return [];
        }
        return $this->withdrawalRepository->getItemsByWithdrawalId((int) $withdrawal->getId());
    }

    public function getOrder()
    {
        $withdrawal = $this->getWithdrawal();
        if (!$withdrawal) {
            return null;
        }
        try {
            return $this->orderRepository->get((int) $withdrawal->getData('order_id'));
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getOrderViewUrl(): string
    {
        $withdrawal = $this->getWithdrawal();
        if (!$withdrawal) {
            return '';
        }
        return $this->getUrl('sales/order/view', ['order_id' => $withdrawal->getData('order_id')]);
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('withdrawal/index/index');
    }

    public function formatWithdrawalDate(string $date): string
    {
        try {
            return (new \DateTime($date))->format('d.m.Y H:i');
        } catch (\Exception $e) {
            return $date;
        }
    }

    public function getStatusLabel(string $status): string
    {
        $labels = [
            'pending'   => __('Pending'),
            'confirmed' => __('Confirmed'),
            'rejected'  => __('Rejected'),
        ];
        return (string) ($labels[$status] ?? $status);
    }
}
