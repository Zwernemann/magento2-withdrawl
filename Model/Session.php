<?php declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model;

use Magento\Framework\App\Request\Http;
use Magento\Framework\App\State;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Session\SaveHandlerInterface;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Session\SessionStartChecker;
use Magento\Framework\Session\SidResolverInterface;
use Magento\Framework\Session\StorageInterface;
use Magento\Framework\Session\ValidatorInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class Session extends SessionManager
{
    private const KEY_WITHDRAWAL_ORDER_ID = 'withdrawal_order_id';
    private const KEY_LAST_WITHDRAWN_ORDER_ID = 'last_withdrawn_order_id';

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        Http $request,
        SidResolverInterface $sidResolver,
        ConfigInterface $sessionConfig,
        SaveHandlerInterface $saveHandler,
        ValidatorInterface $validator,
        StorageInterface $storage,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        State $appState,
        ?SessionStartChecker $sessionStartChecker = null,
    ) {
        parent::__construct(
            $request,
            $sidResolver,
            $sessionConfig,
            $saveHandler,
            $validator,
            $storage,
            $cookieManager,
            $cookieMetadataFactory,
            $appState,
            $sessionStartChecker,
        );
    }

    public function getWithdrawalOrder(): ?OrderInterface
    {
        $orderId = $this->getWithdrawalOrderId();
        if (!$orderId) {
            return null;
        }

        try {
            return $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    public function setWithdrawalOrderId(int $orderId): self
    {
        $this->storage->setData(self::KEY_WITHDRAWAL_ORDER_ID, $orderId);

        return $this;
    }

    public function getWithdrawalOrderId(): ?int
    {
        $value = $this->storage->getData(self::KEY_WITHDRAWAL_ORDER_ID);

        return $value !== null ? (int) $value : null;
    }

    public function unsWithdrawalOrderId(): self
    {
        $this->storage->unsetData(self::KEY_WITHDRAWAL_ORDER_ID);

        return $this;
    }

    public function getLastWithdrawnOrder(): ?OrderInterface
    {
        $orderId = $this->getLastWithdrawnOrderId();
        if (!$orderId) {
            return null;
        }

        try {
            return $this->orderRepository->get($orderId);
        } catch (NoSuchEntityException) {
            return null;
        }
    }

    public function setLastWithdrawnOrderId(int $orderId): self
    {
        $this->storage->setData(self::KEY_LAST_WITHDRAWN_ORDER_ID, $orderId);

        return $this;
    }

    public function getLastWithdrawnOrderId(): ?int
    {
        $value = $this->storage->getData(self::KEY_LAST_WITHDRAWN_ORDER_ID);

        return $value !== null ? (int) $value : null;
    }

    public function unsLastWithdrawnOrderId(): self
    {
        $this->storage->unsetData(self::KEY_LAST_WITHDRAWN_ORDER_ID);

        return $this;
    }
}
