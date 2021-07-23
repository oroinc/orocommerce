<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;

/**
 * Add to frontend products grid information about how much qty of some unit were added to current shopping list
 */
class FrontendProductDatagridListener
{
    const COLUMN_LINE_ITEMS = 'shopping_lists';

    /** @var ProductShoppingListsDataProvider */
    private $productShoppingListsDataProvider;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * FrontendProductDatagridListener constructor.
     */
    public function __construct(
        ProductShoppingListsDataProvider $productShoppingListsDataProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->productShoppingListsDataProvider = $productShoppingListsDataProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_LINE_ITEMS => [
                    'type'          => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY,
                ],
            ]
        );
    }

    public function onResultAfter(SearchResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        $products = [];
        $entityManager = $this->doctrineHelper->getEntityManagerForClass(Product::class);

        foreach ($records as $record) {
            if ($product = $entityManager->getReference(Product::class, $record->getValue('id'))) {
                $products[] = $product;
            }
        }

        $groupedShoppingLists = $this->productShoppingListsDataProvider->getProductsUnitsQuantity($products);

        if (!$groupedShoppingLists) {
            return;
        }

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            if (array_key_exists($productId, $groupedShoppingLists)) {
                $record->addData([self::COLUMN_LINE_ITEMS => $groupedShoppingLists[$productId]]);
            }
        }
    }
}
