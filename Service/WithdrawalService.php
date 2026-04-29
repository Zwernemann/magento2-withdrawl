<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\Email\Sender as EmailSender;
use Zwernemann\Withdrawal\Model\ResourceModel\Withdrawal as WithdrawalResource;
use Zwernemann\Withdrawal\Model\Withdrawal;
use Zwernemann\Withdrawal\Model\WithdrawalFactory;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class WithdrawalService
{
    public function __construct(
        private readonly Config $config,
        private readonly WithdrawalRepository $withdrawalRepository,
        private readonly WithdrawalFactory $withdrawalFactory,
        private readonly WithdrawalResource $withdrawalResource,
        private readonly DateTime $dateTime,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EmailSender $emailSender,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @throws LocalizedException
     */
    public function submit(int $orderId): void
    {
        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            throw new LocalizedException(__('Unable to submit withdrawal request. Please try again.'));
        }

        $this->validate($order);

        try {
            $withdrawal = $this->buildWithdrawal($order);
            $this->withdrawalResource->save($withdrawal);

            $date = $this->dateTime->gmtDate();
            $order->addCommentToStatusHistory(__('Withdrawal requested by customer on %1.', $date));
            $this->orderRepository->save($order);

            $templateVars = $this->buildTemplateVars($order);
            $customerName = $this->resolveCustomerName($order);

            $this->emailSender->sendCustomerEmail($templateVars, $order->getCustomerEmail(), $customerName);
            $this->emailSender->sendAdminEmail($templateVars);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);

            throw new LocalizedException(__('Unable to submit withdrawal request. Please try again.'));
        }
    }

    /**
     * @throws LocalizedException
     */
    private function validate(OrderInterface $order): void
    {
        if ($this->withdrawalRepository->hasWithdrawal((int) $order->getEntityId())) {
            throw new LocalizedException(__('A withdrawal request already exists for this order.'));
        }

        if (!$this->config->isWithdrawalAllowed($order)) {
            throw new LocalizedException(__('The withdrawal period for this order has expired.'));
        }
    }

    private function buildWithdrawal(OrderInterface $order): Withdrawal
    {
        $withdrawal = $this->withdrawalFactory->create();
        $withdrawal->setData([
            'order_id' => (int) $order->getEntityId(),
            'order_increment_id' => $order->getIncrementId(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_name' => $this->resolveCustomerName($order),
            'status' => 'pending',
            'order_created_at' => $order->getCreatedAt(),
            'created_at' => $this->dateTime->gmtDate(),
        ]);

        return $withdrawal;
    }

    private function buildTemplateVars(OrderInterface $order): array
    {
        return [
            'order_increment_id' => $order->getIncrementId(),
            'customer_name' => $this->resolveCustomerName($order),
            'customer_email' => $order->getCustomerEmail(),
            'order_date' => $order->getCreatedAt(),
            'withdrawal_date' => $this->dateTime->gmtDate(),
        ];
    }

    private function resolveCustomerName(OrderInterface $order): string
    {
        $customerName = trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname());
        if ($customerName !== '' && $customerName !== ' ') {
            return $customerName;
        }

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            return trim($billingAddress->getFirstname() . ' ' . $billingAddress->getLastname());
        }

        return '';
    }
}
