<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Withdrawal;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;

class Success extends Template
{
    protected CustomerSession $customerSession;
    protected RequestInterface $request;
    protected OrderRepositoryInterface $orderRepository;
    protected $order;
    protected HttpContext $httpContext;

    public function __construct(
        Context                  $context,
        RequestInterface         $request,
        OrderRepositoryInterface $orderRepository,
        CustomerSession          $customerSession,
        HttpContext              $httpContext,
        array                    $data = []
    )
    {
        parent::__construct($context, $data);
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
    }

    public function getOrder()
    {
        if ($this->order === null) {
            $orderId = (int)$this->request->getParam('order_id');
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

    public function customerLoggedIn(): bool
    {
        return (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }
}
