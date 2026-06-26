<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusCollectionFactory;
use Psr\Log\LoggerInterface;
use Zwernemann\Withdrawal\Api\WithdrawalConfirmationInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\Email\Sender as EmailSender;

class WithdrawalConfirmation implements WithdrawalConfirmationInterface
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        private readonly Config $config,
        private readonly WithdrawalRepository $withdrawalRepository,
        private readonly EmailSender $emailSender,
        private readonly DateTime $dateTime,
        private readonly OrderStatusCollectionFactory $orderStatusCollectionFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    public function sendConfirmation(string $email, string $orderNumber): bool
    {
        $this->assertApiEnabled();

        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            throw new NoSuchEntityException(__('Order number is required.'));
        }

        $order = $this->getEligibleOrder($orderNumber, $email);
        if ($order === null) {
            throw new NoSuchEntityException(__('No order found for order number %1.', $orderNumber));
        }

        if ($this->withdrawalRepository->hasWithdrawal((int) $order->getEntityId())) {
            throw new LocalizedException(__('A withdrawal request already exists for this order.'));
        }

        $storeId = (int) $order->getStoreId();
        $customerName = $this->resolveCustomerName($order);
        $itemsToSave = $this->buildWithdrawalItems($order);

        $withdrawal = $this->withdrawalRepository->createFromOrder($order);
        $this->withdrawalRepository->saveWithdrawalItems((int) $withdrawal->getId(), $itemsToSave);

        $orderComment = __(
            'Withdrawal requested via API on %1.',
            $this->dateTime->gmtDate()
        );
        $order->addCommentToStatusHistory($orderComment);
        $this->orderRepository->save($order);

        $itemLines = [];
        foreach ($itemsToSave as $savedItem) {
            $itemLines[] = sprintf(
                '%s (SKU: %s) x %s',
                $savedItem['name'],
                $savedItem['sku'],
                (int) $savedItem['qty']
            );
        }

        $templateVars = [
            'order_increment_id' => $order->getIncrementId(),
            'customer_name' => $customerName,
            'customer_email' => $order->getCustomerEmail(),
            'order_date' => $order->getCreatedAt(),
            'withdrawal_date' => $this->dateTime->gmtDate(),
            'withdrawal_type_label' => (string) __('withdrawal'),
            'withdrawn_items' => implode("\n", $itemLines),
        ];

        try {
            $this->emailSender->sendCustomerEmail(
                $templateVars,
                (string) $order->getCustomerEmail(),
                $customerName
            );
            $this->emailSender->sendAdminEmail($templateVars);
        } catch (\Exception $e) {
            $this->logger->error(
                'Withdrawal confirmation email failed: ' . $e->getMessage(),
                ['order_number' => $order->getIncrementId(), 'exception' => $e]
            );
            throw new LocalizedException(
                __('Unable to send withdrawal confirmation email. Please try again later.')
            );
        }

        $statusCode = $this->config->getApiOrderStatus($storeId);
        if ($statusCode !== '') {
            $state = $this->getStateForStatus($statusCode);
            if ($state) {
                $order->addStatusHistoryComment(
                    (string) __('Withdrawal confirmation sent via API.')
                )->setIsCustomerNotified(true);
                $order->setState($state)->setStatus($statusCode);
                $this->orderRepository->save($order);
            }
        }

        return true;
    }

    public function canWithdraw(string $email, string $orderNumber): bool
    {
        $this->assertApiEnabled();

        $orderNumber = trim($orderNumber);
        if ($orderNumber === '' || trim($email) === '') {
            return false;
        }

        $order = $this->getEligibleOrder($orderNumber, $email);
        if ($order === null) {
            return false;
        }

        return !$this->withdrawalRepository->hasWithdrawal((int) $order->getEntityId());
    }

    private function assertApiEnabled(): void
    {
        if (!$this->config->isApiEnabled()) {
            throw new NoSuchEntityException(__('Request does not match any route.'));
        }
    }

    private function getEligibleOrder(string $orderNumber, string $email): ?OrderInterface
    {
        if ($orderNumber === '' || trim($email) === '') {
            return null;
        }

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter(OrderInterface::INCREMENT_ID, $orderNumber);
        $searchCriteriaBuilder->addFilter(OrderInterface::CUSTOMER_EMAIL, $email);

        $searchCriteria = $searchCriteriaBuilder->setPageSize(1)->create();
        $items = $this->orderRepository->getList($searchCriteria)->getItems();
        $order = reset($items);

        if (!$order) {
            return null;
        }

        $storeId = (int) $order->getStoreId();
        if (!$this->config->isEnabled($storeId)) {
            return null;
        }

        if (!$this->config->isWithdrawalAllowed($order)) {
            return null;
        }

        return $order;
    }

    private function resolveCustomerName(OrderInterface $order): string
    {
        $customerName = trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname());
        if ($customerName !== '') {
            return $customerName;
        }

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $customerName = trim($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
            if ($customerName !== '') {
                return $customerName;
            }
        }

        return (string) __('Customer');
    }

    /**
     * @return array<int, array{order_item_id: int, name: string, sku: string, qty: float}>
     */
    private function buildWithdrawalItems(OrderInterface $order): array
    {
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = [
                'order_item_id' => (int) $item->getItemId(),
                'name' => (string) $item->getName(),
                'sku' => (string) $item->getSku(),
                'qty' => (float) $item->getQtyOrdered(),
            ];
        }
        return $items;
    }

    private function getStateForStatus(string $statusCode): ?string
    {
        $collection = $this->orderStatusCollectionFactory->create()
            ->joinStates()
            ->addFieldToFilter('main_table.status', $statusCode);
        $item = $collection->getFirstItem();
        return $item->getState() ?: null;
    }
}
