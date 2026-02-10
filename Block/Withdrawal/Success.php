<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Withdrawal;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Success extends Template
{
    private $request;
    private $orderRepository;
    private $order;

    public function __construct(
        Context $context,
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $request;
        $this->orderRepository = $orderRepository;
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

    public function getOrderHistoryUrl(): string
    {
        return $this->getUrl('sales/order/history');
    }

    public function getHomeUrl(): string
    {
        return $this->getUrl('/');
    }
}
