<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

/**
 * Adds information required to highlight low inventory products to storefront product grid.
 */
class ProductDatagridLowInventoryListener
{
    private const SELECT_PATH = '[source][query][select]';
    private const COLUMN_LOW_INVENTORY = 'low_inventory';

    private LowInventoryProvider $lowInventoryProvider;
    private ManagerRegistry $doctrine;

    public function __construct(
        LowInventoryProvider $lowInventoryProvider,
        ManagerRegistry $doctrine
    ) {
        $this->lowInventoryProvider = $lowInventoryProvider;
        $this->doctrine = $doctrine;
    }

    public function onPreBuild(PreBuild $event): void
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            self::SELECT_PATH,
            ['decimal.low_inventory_threshold as low_inventory_threshold']
        );

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_LOW_INVENTORY => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_BOOLEAN,
                ],
            ]
        );
    }

    public function onResultAfter(SearchResultAfter $event): void
    {
        $records = $event->getRecords();
        if (!$records) {
            return;
        }

        $data = [];
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Product::class);
        foreach ($records as $record) {
            $lowInventoryThreshold = $record->getValue('low_inventory_threshold');
            $data[] = [
                'product' => $em->getReference(Product::class, $record->getValue('id')),
                'product_unit' => $em->getReference(ProductUnit::class, $record->getValue('unit')),
                'low_inventory_threshold' => $lowInventoryThreshold ?: -1,
                'highlight_low_inventory' => (bool)$lowInventoryThreshold
            ];
        }

        $lowInventoryResponse = $this->lowInventoryProvider->isLowInventoryCollection($data);
        if (!$lowInventoryResponse) {
            return;
        }

        foreach ($records as $record) {
            $record->addData([
                self::COLUMN_LOW_INVENTORY => $lowInventoryResponse[$record->getValue('id')] ?? false
            ]);
        }
    }
}
