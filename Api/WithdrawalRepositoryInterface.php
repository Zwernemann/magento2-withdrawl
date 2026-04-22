<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Api;

interface WithdrawalRepositoryInterface
{
    /**
     * @param int $orderId
     * @param string|null $comment
     * @return mixed
     */
    public function create($orderId, $comment = null);

    /**
     * @return array
     */
    public function getList();

    /**
     * @param int $entityId
     * @return \Zwernemann\Withdrawal\Model\Withdrawal
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $entityId);

    /**
     * @param int $entityId
     * @param string $status
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function updateStatus(int $entityId, string $status): void;
}
