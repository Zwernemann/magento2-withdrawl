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
    protected $transportBuilder;
    protected $storeManager;
    protected $config;
    protected $logger;

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

    public function sendCustomerEmail(
        array $templateVars,
        string $customerEmail,
        string $customerName,
        array $withdrawnItems = [],
        array $nonWithdrawnItems = [],
        string $withdrawalType = 'full',
        bool $isUpdate = false
    ): void {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $sender = $this->config->getEmailSender((int) $storeId);

            // Use update template if this is an update
            if ($isUpdate) {
                $templateId = 'zwernemann_withdrawal_email_customer_update_template';
            } else {
                $templateId = $this->config->getCustomerEmailTemplate((int) $storeId);
            }

            $adminEmail = $this->getAdminEmail((int) $storeId);

            // Add item lists to template vars
            $withdrawnItemsHtml = $this->buildItemListHtml($withdrawnItems);
            $nonWithdrawnItemsHtml = $this->buildItemListHtml($nonWithdrawnItems);

            $templateVars['withdrawn_items_html'] = $withdrawnItemsHtml;
            $templateVars['non_withdrawn_items_html'] = $nonWithdrawnItemsHtml;
            $templateVars['has_withdrawn_items'] = !empty($withdrawnItems) ? '1' : '';
            $templateVars['has_non_withdrawn_items'] = !empty($nonWithdrawnItems) ? '1' : '';
            $templateVars['withdrawal_type_label'] = $withdrawalType === 'partial'
                ? __('Partial Withdrawal')->render()
                : __('Full Withdrawal')->render();
            $templateVars['is_partial_withdrawal'] = $withdrawalType === 'partial' ? '1' : '';

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

    public function sendAdminEmail(
        array $templateVars,
        array $withdrawnItems = [],
        array $nonWithdrawnItems = [],
        string $withdrawalType = 'full',
        bool $isUpdate = false
    ): void {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            $sender = $this->config->getEmailSender((int) $storeId);
            $adminEmail = $this->getAdminEmail((int) $storeId);

            if (!$adminEmail) {
                return;
            }

            // Use different template for updates
            if ($isUpdate) {
                $templateId = 'zwernemann_withdrawal_email_admin_update_template';
            } else {
                $templateId = $this->config->getAdminEmailTemplate((int) $storeId);
            }

            // Add item lists to template vars
            $withdrawnItemsHtml = $this->buildItemListHtml($withdrawnItems);
            $nonWithdrawnItemsHtml = $this->buildItemListHtml($nonWithdrawnItems);

            $templateVars['withdrawn_items_html'] = $withdrawnItemsHtml;
            $templateVars['non_withdrawn_items_html'] = $nonWithdrawnItemsHtml;
            $templateVars['has_withdrawn_items'] = !empty($withdrawnItems) ? '1' : '';
            $templateVars['has_non_withdrawn_items'] = !empty($nonWithdrawnItems) ? '1' : '';
            $templateVars['withdrawal_type_label'] = $withdrawalType === 'partial'
                ? __('Partial Withdrawal')->render()
                : __('Full Withdrawal')->render();
            $templateVars['is_partial_withdrawal'] = $withdrawalType === 'partial' ? '1' : '';

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

    protected function buildItemListHtml(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $html = '<table style="border-collapse: collapse; width: 100%; margin: 10px 0;">';
        $html .= '<thead><tr>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">'. __('Product Name')->render() .'</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: left;">'. __('SKU')->render() .'</th>';
        $html .= '<th style="border: 1px solid #ddd; padding: 8px; text-align: center;">'. __('Qty')->render() .'</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';

        foreach ($items as $item) {
            $html .= '<tr>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">'. htmlspecialchars($item->getName()) .'</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px;">'. htmlspecialchars($item->getSku()) .'</td>';
            $html .= '<td style="border: 1px solid #ddd; padding: 8px; text-align: center;">'. (int)$item->getQtyOrdered() .'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    protected function getAdminEmail(int $storeId): string
    {
        $email = $this->config->getNotificationEmail($storeId);
        if (!$email) {
            $email = $this->storeManager->getStore($storeId)->getConfig('trans_email/ident_general/email');
        }
        return (string) $email;
    }
}

