<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

/**
 * Add highlight low inventory of the products on product grid
 */
class ProductDatagridLowInventoryListener
{
    const COLUMN_LOW_INVENTORY = 'low_inventory';

    /** @var LowInventoryProvider */
    private $lowInventoryProvider;

    /** DoctrineHelper */
    private $doctrineHelper;

    public function __construct(
        LowInventoryProvider $lowInventoryProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->lowInventoryProvider = $lowInventoryProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();

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

    public function onResultAfter(SearchResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $products = $this->getProductsEntities($records);
        $data = $this->prepareDataForIsLowInventoryCollection($products);
        $lowInventoryResponse = $this->lowInventoryProvider->isLowInventoryCollection($data);

        if (empty($lowInventoryResponse)) {
            return;
        }

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            $lowInventoryValue = false;
            if (array_key_exists($productId, $lowInventoryResponse)) {
                $lowInventoryValue = $lowInventoryResponse[$productId];
            }
            $record->addData([self::COLUMN_LOW_INVENTORY => $lowInventoryValue]);
        }
    }

    /**
     * @param Product[] $products
     *
     * @return array
     */
    protected function prepareDataForIsLowInventoryCollection(array $products)
    {
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'product' => $product,
            ];
        }

        return $data;
    }

    /**
     * @param ResultRecord[] $records
     *
     * @return Product[]
     */
    protected function getProductsEntities(array $records)
    {
        $products = [];

        /** @var ResultRecord[] $records */
        foreach ($records as $record) {
            $products[] = $record->getValue('id');
        }

        /** @var ProductRepository $productRepository */
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);

        return $productRepository->findBy(['id' => $products]);
    }
}
