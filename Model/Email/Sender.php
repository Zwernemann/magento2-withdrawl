<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Model\Email;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Zwernemann\Withdrawal\Helper\Config;
use Psr\Log\LoggerInterface;

class Sender
{
    private $transportBuilder;
    private $storeManager;
    private $config;
    private $logger;

    public function __construct(
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function sendCustomerEmail(array $templateVars, string $customerEmail, string $customerName): void
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $sender = $this->config->getEmailSender((int) $storeId);
            $templateId = $this->config->getCustomerEmailTemplate((int) $storeId);

            $adminEmail = $this->getAdminEmail((int) $storeId);

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($sender, $storeId)
                ->addTo($customerEmail, $customerName);

            if ($adminEmail) {
                $transport->addBcc($adminEmail);
            }

            $transport->getTransport()->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Withdrawal customer email error: ' . $e->getMessage());
        }
    }

    public function sendAdminEmail(array $templateVars): void
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $sender = $this->config->getEmailSender((int) $storeId);
            $templateId = $this->config->getAdminEmailTemplate((int) $storeId);
            $adminEmail = $this->getAdminEmail((int) $storeId);

            if (!$adminEmail) {
                return;
            }

            $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area' => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope($sender, $storeId)
                ->addTo($adminEmail)
                ->getTransport()
                ->sendMessage();
        } catch (\Exception $e) {
            $this->logger->error('Withdrawal admin email error: ' . $e->getMessage());
        }
    }

    private function getAdminEmail(int $storeId): string
    {
        $email = $this->config->getNotificationEmail($storeId);
        if (!$email) {
            $email = $this->storeManager->getStore($storeId)->getConfig('trans_email/ident_general/email');
        }
        return (string) $email;
    }
}
