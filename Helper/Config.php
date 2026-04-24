<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Psr\Log\LoggerInterface;

class Config extends AbstractHelper
{
    const XML_PATH_ENABLED = 'zwernemann_withdrawal/general/enabled';
    const XML_PATH_NOTIFICATION_EMAIL = 'zwernemann_withdrawal/general/email';
    const XML_PATH_WITHDRAWAL_PERIOD = 'zwernemann_withdrawal/general/withdrawal_period';
    const XML_PATH_EMAIL_TEMPLATE_CUSTOMER = 'zwernemann_withdrawal/email/customer_template';
    const XML_PATH_EMAIL_TEMPLATE_ADMIN = 'zwernemann_withdrawal/email/admin_template';
    const XML_PATH_EMAIL_SENDER = 'zwernemann_withdrawal/email/sender';
    const XML_PATH_ALLOWED_ORDER_STATUSES = 'zwernemann_withdrawal/general/allowed_order_statuses';
    const XML_PATH_EXCLUDED_ATTRIBUTES = 'zwernemann_withdrawal/general/excluded_product_attributes';

    private ShipmentCollectionFactory $shipmentCollectionFactory;
    private ProductRepositoryInterface $productRepository;
    private ProductCollectionFactory $productCollectionFactory;
    private LoggerInterface $logger;
    private $withdrawalRepository;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        ShipmentCollectionFactory $shipmentCollectionFactory,
        ProductRepositoryInterface $productRepository,
        ProductCollectionFactory $productCollectionFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->productRepository = $productRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->logger = $logger;
    }

    public function setWithdrawalRepository($withdrawalRepository)
    {
        $this->withdrawalRepository = $withdrawalRepository;
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

    public function getExcludedProductAttributes($storeId = null): array
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_EXCLUDED_ATTRIBUTES,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if (empty($value)) {
            return [];
        }

        $attributes = explode(',', $value);
        return array_map('trim', array_filter($attributes));
    }

    public function isItemWithdrawable(\Magento\Sales\Api\Data\OrderItemInterface $item): bool
    {
        $excludedAttributes = $this->getExcludedProductAttributes();

        if (empty($excludedAttributes)) {
            return true;
        }

        try {
            $product = $this->productRepository->getById($item->getProductId());

            foreach ($excludedAttributes as $attributeCode) {
                try {
                    $attributeValue = $product->getData($attributeCode);

                    // Check if attribute is set to true/1/Yes
                    if ($attributeValue === true || $attributeValue === 1 || $attributeValue === '1' || strtolower((string)$attributeValue) === 'yes') {
                        return false;
                    }
                } catch (\Exception $e) {
                    $this->logger->debug('Withdrawal attribute check failed', [
                        'item_id' => $item->getId(),
                        'product_id' => $item->getProductId(),
                        'attribute' => $attributeCode,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } catch (\Exception $e) {
            $this->logger->debug('Withdrawal product load failed', [
                'item_id' => $item->getId(),
                'product_id' => $item->getProductId(),
                'error' => $e->getMessage()
            ]);
            return true; // If product can't be loaded, allow withdrawal
        }

        return true;
    }

    public function getWithdrawableItems(\Magento\Sales\Api\Data\OrderInterface $order, array $excludedItemIds = []): array
    {
        $excludedAttributes = $this->getExcludedProductAttributes();
        $withdrawableItems = [];

        // Get all visible items
        $orderItems = $order->getAllVisibleItems();

        if (empty($excludedAttributes)) {
            // No exclusions, just filter by excluded IDs
            foreach ($orderItems as $item) {
                if (!in_array($item->getId(), $excludedItemIds)) {
                    $withdrawableItems[] = $item;
                }
            }
            return $withdrawableItems;
        }

        // Build product IDs array for collection
        $productIds = [];
        foreach ($orderItems as $item) {
            $productIds[] = $item->getProductId();
        }

        if (empty($productIds)) {
            return [];
        }

        // Load product collection with attributes for performance
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addIdFilter($productIds);
        $productCollection->addAttributeToSelect($excludedAttributes);

        $products = [];
        foreach ($productCollection as $product) {
            $products[$product->getId()] = $product;
        }

        // Filter items
        foreach ($orderItems as $item) {
            if (in_array($item->getId(), $excludedItemIds)) {
                continue;
            }

            if ($this->isItemWithdrawable($item)) {
                $withdrawableItems[] = $item;
            }
        }

        return $withdrawableItems;
    }

    public function getNonWithdrawableItems(\Magento\Sales\Api\Data\OrderInterface $order): array
    {
        $excludedAttributes = $this->getExcludedProductAttributes();

        if (empty($excludedAttributes)) {
            return [];
        }

        $nonWithdrawableItems = [];
        $orderItems = $order->getAllVisibleItems();

        foreach ($orderItems as $item) {
            if (!$this->isItemWithdrawable($item)) {
                $nonWithdrawableItems[] = $item;
            }
        }

        return $nonWithdrawableItems;
    }

    public function getAlreadyWithdrawnItemIds(int $orderId): array
    {
        if (!$this->withdrawalRepository) {
            return [];
        }

        return $this->withdrawalRepository->getWithdrawnItemIds($orderId);
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

        $allowedStatuses = $this->getAllowedOrderStatuses();
        if (!empty($allowedStatuses) && !in_array($order->getStatus(), $allowedStatuses)) {
            return false;
        }

        
        $shipmentDate = $this->getLatestShipmentDate($order);

        if ($shipmentDate === null) {
            // Not yet shipped: check if there are withdrawable items
            $alreadyWithdrawn = $this->getAlreadyWithdrawnItemIds((int)$order->getEntityId());
            $withdrawable = $this->getWithdrawableItems($order, $alreadyWithdrawn);
            return count($withdrawable) > 0;
        }

        $now = new \DateTime();
        $diff = $now->diff($shipmentDate);
        $daysDiff = (int) $diff->days;

        if ($daysDiff > $this->getWithdrawalPeriodDays()) {
            return false;
        }

        // Check if there are still withdrawable items
        $alreadyWithdrawn = $this->getAlreadyWithdrawnItemIds((int)$order->getEntityId());
        $withdrawable = $this->getWithdrawableItems($order, $alreadyWithdrawn);
        return count($withdrawable) > 0;
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
