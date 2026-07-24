<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_ENABLED = 'zwernemann_withdrawal/general/enabled';
    const XML_PATH_NOTIFICATION_EMAIL = 'zwernemann_withdrawal/general/email';
    const XML_PATH_WITHDRAWAL_PERIOD = 'zwernemann_withdrawal/general/withdrawal_period';
    const XML_PATH_EMAIL_TEMPLATE_CUSTOMER = 'zwernemann_withdrawal/email/customer_template';
    const XML_PATH_EMAIL_TEMPLATE_ADMIN = 'zwernemann_withdrawal/email/admin_template';
    const XML_PATH_EMAIL_SENDER = 'zwernemann_withdrawal/email/sender';
    const XML_PATH_ALLOWED_ORDER_STATUSES = 'zwernemann_withdrawal/general/allowed_order_statuses';
    const XML_PATH_ALLOW_PARTIAL_WITHDRAWAL = 'zwernemann_withdrawal/general/allow_partial_withdrawal';
    const XML_PATH_API_ORDER_STATUS = 'zwernemann_withdrawal/api/order_status';
    const XML_PATH_API_ENABLED = 'zwernemann_withdrawal/api/enabled';

    private ShipmentCollectionFactory $shipmentCollectionFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        ShipmentCollectionFactory $shipmentCollectionFactory
    ) {
        parent::__construct($context);
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
    }

    public function isEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getNotificationEmail($storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_NOTIFICATION_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getWithdrawalPeriodDays($storeId = null): int
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_WITHDRAWAL_PERIOD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ? (int) $value : 14;
    }

    public function isPartialWithdrawalAllowed($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ALLOW_PARTIAL_WITHDRAWAL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getAllowedOrderStatuses($storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_ALLOWED_ORDER_STATUSES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ? explode(',', $value) : [];
    }

    public function getCustomerEmailTemplate($storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE_CUSTOMER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ?: 'zwernemann_withdrawal_email_customer_template';
    }

    public function getAdminEmailTemplate($storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_TEMPLATE_ADMIN,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ?: 'zwernemann_withdrawal_email_admin_template';
    }

    public function getEmailSender($storeId = null): string
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ?: 'general';
    }

    public function getApiOrderStatus($storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_API_ORDER_STATUS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isApiEnabled($storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_API_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    private function getLatestShipmentDate(\Magento\Sales\Api\Data\OrderInterface $order): ?\DateTime
    {
        $collection = $this->shipmentCollectionFactory->create();
        $collection->setOrderFilter($order->getEntityId());
        $collection->setOrder('created_at', 'DESC');
        $collection->setPageSize(1);

        $shipment = $collection->getFirstItem();

        if ($shipment && $shipment->getId()) {
            return new \DateTime($shipment->getCreatedAt());
        }

        return null;
    }
    
    public function isWithdrawalAllowed(\Magento\Sales\Api\Data\OrderInterface $order): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        // The order status must be in the allowed list (when configured).
        // This has to be checked regardless of shipment state, otherwise
        // unshipped orders (e.g. "Nová objednávka") would bypass the filter.
        $allowedStatuses = $this->getAllowedOrderStatuses();
        if (!empty($allowedStatuses) && !in_array($order->getStatus(), $allowedStatuses, true)) {
            return false;
        }

        // A physical order not yet shipped → allowed (goods not received, the
        // withdrawal clock hasn't started). Virtual orders never ship, so they
        // must not take this free pass.
        $shipmentDate = $this->getLatestShipmentDate($order);
        if (!$order->getIsVirtual() && $shipmentDate === null) {
            return true;
        }

        // Measure the withdrawal period against the shipment date; for virtual
        // orders (no shipment) fall back to the order date, since delivery
        // never occurs and the goods are received at purchase.
        $referenceDate = $shipmentDate ?? new \DateTime($order->getCreatedAt());

        $now = new \DateTime();
        return (int) $now->diff($referenceDate)->days <= $this->getWithdrawalPeriodDays();
    }

    public function getWithdrawalDeadline(\Magento\Sales\Api\Data\OrderInterface $order): string
    {
        $shipmentDate = $this->getLatestShipmentDate($order);

        if ($shipmentDate === null) {
            return '';
        }

        $shipmentDate->modify('+' . $this->getWithdrawalPeriodDays() . ' days');
        return $shipmentDate->format('d.m.Y');
    }
}
