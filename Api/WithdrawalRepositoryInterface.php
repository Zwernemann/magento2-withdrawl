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
}
