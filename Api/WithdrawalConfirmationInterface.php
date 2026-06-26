<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Api;

/**
 * REST API for withdrawal eligibility checks and confirmation.
 */
interface WithdrawalConfirmationInterface
{
    /**
     * Register a withdrawal request, send notification emails, and optionally update order status.
     *
     * @param string $email Customer email (must match the order's customer email).
     * @param string $orderNumber Order increment ID.
     * @return bool True on success.
     * @throws \Magento\Framework\Exception\NoSuchEntityException If order is not found.
     * @throws \Magento\Framework\Exception\LocalizedException On validation or processing failure.
     */
    public function sendConfirmation(string $email, string $orderNumber): bool;

    /**
     * Check whether an order can be withdrawn (exists, email matches, and eligibility rules pass).
     * Returns only true/false for privacy; no order data is exposed.
     *
     * @param string $email Customer email.
     * @param string $orderNumber Order increment ID.
     * @return bool
     */
    public function canWithdraw(string $email, string $orderNumber): bool;
}
