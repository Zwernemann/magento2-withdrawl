<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Block\Withdrawal;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Zwernemann\Withdrawal\Model\WithdrawalRepository;

class View extends Template
{
    protected CustomerSession $customerSession;
    protected RequestInterface $request;
    protected OrderRepositoryInterface $orderRepository;
    protected Config $config;
    protected WithdrawalRepository $withdrawalRepository;
    protected $order;
    protected HttpContext $httpContext;
    protected $withdrawableItems = null;
    protected $nonWithdrawableItems = null;
    protected $alreadyWithdrawnItems = null;

    public function __construct(
        Context                  $context,
        RequestInterface         $request,
        OrderRepositoryInterface $orderRepository,
        Config                   $config,
        WithdrawalRepository     $withdrawalRepository,
        CustomerSession          $customerSession,
        HttpContext              $httpContext,
        array                    $data = []
    )
    {
        parent::__construct($context, $data);
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->withdrawalRepository = $withdrawalRepository;
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
    }

    public function getSubmitUrl(): string
    {
        return $this->getUrl('withdrawal/index/submit');
    }

    public function isWithdrawalAllowed(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }
        return $this->config->isWithdrawalAllowed($order);
    }

    public function getOrder()
    {
        if ($this->order === null) {
            $orderId = (int)$this->request->getParam('order_id');
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

    public function hasExistingWithdrawal(): bool
    {
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }
        return $this->withdrawalRepository->hasWithdrawal((int)$order->getEntityId());
    }

    public function getWithdrawalDeadline(): string
    {
        $order = $this->getOrder();
        if (!$order) {
            return '';
        }
        return $this->config->getWithdrawalDeadline($order);
    }

    public function customerLoggedIn(): bool
    {
        return (bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    public function getGuestEmail(): string
    {
        // Get email from guest withdrawal session
        return (string)$this->customerSession->getGuestWithdrawalEmail();
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

    public function hasNonWithdrawableItems(): bool
    {
        return count($this->getNonWithdrawableItems()) > 0;
    }

    public function getNonWithdrawableItems(): array
    {
        if ($this->nonWithdrawableItems === null) {
            $order = $this->getOrder();
            if (!$order) {
                $this->nonWithdrawableItems = [];
                return $this->nonWithdrawableItems;
            }

            $this->nonWithdrawableItems = $this->config->getNonWithdrawableItems($order);
        }

        return $this->nonWithdrawableItems;
    }

    public function hasAlreadyWithdrawnItems(): bool
    {
        return count($this->getAlreadyWithdrawnItems()) > 0;
    }

    public function getAlreadyWithdrawnItems(): array
    {
        if ($this->alreadyWithdrawnItems === null) {
            $order = $this->getOrder();
            if (!$order) {
                $this->alreadyWithdrawnItems = [];
                return $this->alreadyWithdrawnItems;
            }

            $withdrawnItemIds = $this->config->getAlreadyWithdrawnItemIds((int)$order->getEntityId());
            $this->alreadyWithdrawnItems = [];

            if (!empty($withdrawnItemIds)) {
                foreach ($order->getAllVisibleItems() as $item) {
                    if (in_array($item->getId(), $withdrawnItemIds)) {
                        $this->alreadyWithdrawnItems[] = $item;
                    }
                }
            }
        }

        return $this->alreadyWithdrawnItems;
    }

    /**
     * Get withdrawable item IDs as JSON string
     *
     * @return string
     */
    public function getWithdrawableItemIdsJson(): string
    {
        $items = $this->getWithdrawableItems();
        $itemIds = array_map(function ($item) {
            return (int)$item->getId();
        }, $items);

        return json_encode($itemIds, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function getWithdrawableItems(): array
    {
        if ($this->withdrawableItems === null) {
            $order = $this->getOrder();
            if (!$order) {
                $this->withdrawableItems = [];
                return $this->withdrawableItems;
            }

            $alreadyWithdrawn = $this->config->getAlreadyWithdrawnItemIds((int)$order->getEntityId());
            $this->withdrawableItems = $this->config->getWithdrawableItems($order, $alreadyWithdrawn);
        }

        return $this->withdrawableItems;
    }

    /**
     * Get validation message for form submission
     *
     * @return string
     */
    public function getValidationMessage(): string
    {
        return (string)__('Please select at least one item to withdraw.');
    }

    /**
     * Get translation for "of" text
     *
     * @return string
     */
    public function getOfText(): string
    {
        return (string)__('of');
    }

    /**
     * Get translation for "selected" text
     *
     * @return string
     */
    public function getSelectedText(): string
    {
        return (string)__('selected');
    }

    /**
     * Get confirmation message template
     *
     * @return string
     */
    public function getConfirmationMessageTemplate(): string
    {
        return (string)__('You are about to withdraw {count} item(s) from this order.');
    }

    /**
     * Get all visible items that are withdrawable (excludes items with excluded attributes)
     *
     * @return array
     */
    public function getAllWithdrawableVisibleItems(): array
    {
        $order = $this->getOrder();
        if (!$order) {
            return [];
        }

        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            if ($this->config->isItemWithdrawable($item)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Get items that have been withdrawn (from zwernemann_withdrawal table)
     *
     * @return array
     */
    public function getWithdrawnItems(): array
    {
        $order = $this->getOrder();
        if (!$order) {
            return [];
        }

        $withdrawnItemIds = $this->config->getAlreadyWithdrawnItemIds((int)$order->getEntityId());
        if (empty($withdrawnItemIds)) {
            return [];
        }

        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            if (in_array($item->getId(), $withdrawnItemIds)) {
                $items[] = $item;
            }
        }

        return $items;
    }
}
