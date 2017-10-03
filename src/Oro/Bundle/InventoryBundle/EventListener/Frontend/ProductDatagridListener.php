<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryQuantityManager;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

/**
 * Add highlight low inventory of the products on product grid
 */
class ProductDatagridListener
{
    const COLUMN_LOW_INVENTORY = 'low_inventory';

    /** @var LowInventoryQuantityManager */
    private $lowInventoryQuantityManager;

    /**
     * @param LowInventoryQuantityManager $lowInventoryQuantityManager
     */
    public function __construct(LowInventoryQuantityManager $lowInventoryQuantityManager)
    {
        $this->lowInventoryQuantityManager = $lowInventoryQuantityManager;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_LOW_INVENTORY => [
                    'type'          => 'field',
                    'frontend_type' => PropertyInterface::TYPE_BOOLEAN,
                ],
            ]
        );
    }

    /**
     * @param SearchResultAfter $event
     */
    public function onResultAfter(SearchResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $products = [];

        foreach ($records as $record) {
            $products[] = [
                'productId'   => $record->getValue('id'),
                'productUnit' => $record->getValue('unit')
            ];
        }

        $lowInventoryResponse = $this->lowInventoryQuantityManager->isLowInventoryCollection($products);

        if (empty($lowInventoryResponse)) {
            return;
        }

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            if (array_key_exists($productId, $lowInventoryResponse)) {
                $record->addData([self::COLUMN_LOW_INVENTORY => $lowInventoryResponse[$productId]]);
            }
        }
    }
}
