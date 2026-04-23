<?php
declare(strict_types=1);

namespace Zwernemann\Withdrawal\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    private $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    $actions = [
                        'view_order' => [
                            'href' => $this->urlBuilder->getUrl(
                                'sales/order/view',
                                ['order_id' => $item['order_id']]
                            ),
                            'label' => __('View Order'),
                        ],
                    ];

                    if ($item['status'] !== 'confirmed') {
                        $actions['confirm'] = [
                            'href' => $this->urlBuilder->getUrl(
                                'withdrawal/index/updatestatus',
                                ['id' => $item['entity_id'], 'status' => 'confirmed']
                            ),
                            'label' => __('Confirm'),
                            'confirm' => [
                                'title' => __('Confirm Withdrawal'),
                                'message' => __('Are you sure you want to confirm this withdrawal request?'),
                            ],
                        ];
                    }

                    if ($item['status'] !== 'rejected') {
                        $actions['reject'] = [
                            'href' => $this->urlBuilder->getUrl(
                                'withdrawal/index/updatestatus',
                                ['id' => $item['entity_id'], 'status' => 'rejected']
                            ),
                            'label' => __('Reject'),
                            'confirm' => [
                                'title' => __('Reject Withdrawal'),
                                'message' => __('Are you sure you want to reject this withdrawal request?'),
                            ],
                        ];
                    }

                    $item[$this->getData('name')] = $actions;
                }
            }
        }
        return $dataSource;
    }
}
