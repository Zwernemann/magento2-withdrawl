<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Service;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;

class EmailSender
{
    protected TransportBuilder $transportBuilder;
    protected StateInterface $inlineTranslation;
    protected StoreManagerInterface $storeManager;

    public function __construct(
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        StoreManagerInterface $storeManager
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->storeManager = $storeManager;
    }

    /**
     * Send guest access email with token link
     *
     * @param OrderInterface $order
     * @param string $email
     * @param string $token
     * @return void
     * @throws \Exception
     */
    public function sendGuestAccessEmail(OrderInterface $order, string $email, string $token): void
    {
        $this->inlineTranslation->suspend();

        try {
            $store = $this->storeManager->getStore($order->getStoreId());
            $tokenUrl = $store->getUrl('withdrawal/guest/verify', ['token' => $token]);

            $transport = $this->transportBuilder
                ->setTemplateIdentifier('zwernemann_withdrawal_guest_access_link')
                ->setTemplateOptions([
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $store->getId(),
                ])
                ->setTemplateVars([
                    'order' => $order,
                    'token_url' => $tokenUrl,
                ])
                ->setFromByScope('general')
                ->addTo($email)
                ->getTransport();

            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}

