<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Service;

use Zwernemann\Withdrawal\Model\GuestTokenFactory;
use Zwernemann\Withdrawal\Model\ResourceModel\GuestToken as GuestTokenResource;
use Zwernemann\Withdrawal\Model\ResourceModel\GuestToken\CollectionFactory;
use Psr\Log\LoggerInterface;

class GuestTokenManager
{
    protected GuestTokenFactory $tokenFactory;
    protected GuestTokenResource $tokenResource;
    protected CollectionFactory $collectionFactory;
    protected LoggerInterface $logger;

    public function __construct(
        GuestTokenFactory $tokenFactory,
        GuestTokenResource $tokenResource,
        CollectionFactory $collectionFactory,
        LoggerInterface $logger
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->tokenResource = $tokenResource;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    /**
     * Generate a new access token for guest withdrawal
     *
     * @param int $orderId
     * @param string $email
     * @return string
     * @throws \Exception
     */
    public function generateToken(int $orderId, string $email): string
    {
        $token = bin2hex(random_bytes(32));

        $guestToken = $this->tokenFactory->create();
        $guestToken->setData([
            'order_id' => $orderId,
            'email' => $email,
            'token' => $token
        ]);

        try {
            $this->tokenResource->save($guestToken);

            $this->logger->info('Guest withdrawal token generated', [
                'order_id' => $orderId,
                'email' => $email,
                'token' => substr($token, 0, 8) . '...'
            ]);

            return $token;
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate guest withdrawal token', [
                'order_id' => $orderId,
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate token and return order/email data
     *
     * @param string $token
     * @return array|null ['order_id' => int, 'email' => string] or null if invalid
     */
    public function validateToken(string $token): ?array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('token', $token);
        $tokenModel = $collection->getFirstItem();

        if (!$tokenModel->getId()) {
            $this->logger->warning('Invalid guest withdrawal token attempted', [
                'token' => substr($token, 0, 8) . '...'
            ]);
            return null;
        }

        $this->logger->info('Guest withdrawal token validated', [
            'order_id' => $tokenModel->getData('order_id'),
            'email' => $tokenModel->getData('email')
        ]);

        return [
            'order_id' => (int)$tokenModel->getData('order_id'),
            'email' => $tokenModel->getData('email')
        ];
    }
}

