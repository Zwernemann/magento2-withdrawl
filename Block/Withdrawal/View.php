<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Withdrawal;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Session\Generic as Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Tax\Model\Config as TaxConfig;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class View extends Template
{
    private $request;
    private $orderRepository;
    private $config;
    private $withdrawalRepository;
    private $session;
    private $customerSession;
    private $taxConfig;
    private $order;

    /** @var int[]|null */
    private $withdrawnItemIds = null;

    public function __construct(
        Context $context,
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        Config $config,
        WithdrawalRepository $withdrawalRepository,
        Session $session,
        CustomerSession $customerSession,
        TaxConfig $taxConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->withdrawalRepository = $withdrawalRepository;
        $this->session = $session;
        $this->customerSession = $customerSession;
        $this->taxConfig = $taxConfig;
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

    public function isPartialWithdrawalAllowed(): bool
    {
        return $this->config->isPartialWithdrawalAllowed();
    }

    public function isWithdrawalAllowed(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }
        return $this->config->isWithdrawalAllowed($order);
    }

    /**
     * Returns order_item_ids that have already been covered by a withdrawal request.
     *
     * @return int[]
     */
    public function getWithdrawnOrderItemIds(): array
    {
        if ($this->withdrawnItemIds === null) {
            $order = $this->getOrder();
            $this->withdrawnItemIds = $order
                ? $this->withdrawalRepository->getWithdrawnOrderItemIds((int) $order->getEntityId())
                : [];
        }
        return $this->withdrawnItemIds;
    }

    /**
     * Returns true only when every visible order item has already been withdrawn.
     */
    public function hasExistingWithdrawal(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }

        $withdrawnIds = $this->getWithdrawnOrderItemIds();
        if (empty($withdrawnIds)) {
            return false;
        }

        $allItemIds = array_map(
            fn($item) => (int) $item->getItemId(),
            $order->getAllVisibleItems()
        );

        return count(array_diff($allItemIds, $withdrawnIds)) === 0;
    }

    /**
     * Returns true when some items are already withdrawn but not all.
     */
    public function hasPartialWithdrawal(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }

        $withdrawnIds = $this->getWithdrawnOrderItemIds();
        if (empty($withdrawnIds)) {
            return false;
        }

        $allItemIds = array_map(
            fn($item) => (int) $item->getItemId(),
            $order->getAllVisibleItems()
        );

        $remaining = array_diff($allItemIds, $withdrawnIds);
        return count($remaining) > 0;
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
        return !$this->customerSession->isLoggedIn()
            && !empty($this->session->getGuestWithdrawalEmail());
    }

    public function getGuestEmail(): string
    {
        return (string) $this->session->getGuestWithdrawalEmail();
    }

    /**
     * Unit price to display for an order item, matching the store's tax
     * display configuration (gross for typical B2C shops, net for B2B).
     *
     * @param OrderItemInterface $item
     * @return float
     */
    public function getItemDisplayPrice($item): float
    {
        return $this->displayPricesInclTax()
            ? (float) $item->getPriceInclTax()
            : (float) $item->getPrice();
    }

    /**
     * Row total to display for an order item, matching the store's tax
     * display configuration (gross for typical B2C shops, net for B2B).
     *
     * @param OrderItemInterface $item
     * @return float
     */
    public function getItemDisplayRowTotal($item): float
    {
        return $this->displayPricesInclTax()
            ? (float) $item->getRowTotalInclTax()
            : (float) $item->getRowTotal();
    }

    /**
     * Whether sales prices should be shown incl. tax, based on the store's
     * "Orders, Invoices, Credit Memos Display Settings" (tax/sales_display).
     * "Including" and "Both" are treated as gross so the customer sees the
     * amount actually paid.
     */
    private function displayPricesInclTax(): bool
    {
        $order = $this->getOrder();
        $store = $order ? $order->getStoreId() : null;

        return $this->taxConfig->displaySalesPriceInclTax($store)
            || $this->taxConfig->displaySalesBothPrices($store);
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
