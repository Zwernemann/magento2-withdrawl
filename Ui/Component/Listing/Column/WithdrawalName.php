<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Zwernemann\Withdrawal\Model\NameComparison;

/**
 * Shows the name the consumer declared on the withdrawal form and marks rows
 * where that name differs from the name on the order, so the merchant can take
 * a closer look before confirming or rejecting the request.
 */
class WithdrawalName extends Column
{
    private NameComparison $nameComparison;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        NameComparison $nameComparison,
        array $components = [],
        array $data = []
    ) {
        $this->nameComparison = $nameComparison;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = (string) $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            $declaredName = trim(
                (string) ($item['withdrawal_firstname'] ?? '')
                . ' '
                . (string) ($item['withdrawal_lastname'] ?? '')
            );

            if ($declaredName === '') {
                $item[$fieldName] = '';
                continue;
            }

            $orderName = trim((string) ($item['customer_name'] ?? ''));
            $differs = $orderName !== '' && !$this->nameComparison->isSame($declaredName, $orderName);

            $item[$fieldName] = $differs
                ? $declaredName . ' ' . __('(name differs from order)')
                : $declaredName;
        }

        return $dataSource;
    }
}
