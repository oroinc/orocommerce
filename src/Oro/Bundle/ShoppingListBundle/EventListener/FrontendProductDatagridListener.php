<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;

/**
 * Add to frontend products grid information about how much qty of some unit were added to current shopping list
 */
class FrontendProductDatagridListener
{
    private const COLUMN_SHOPPING_LISTS = 'shopping_lists';

    private ProductShoppingListsDataProvider $productShoppingListsDataProvider;
    private ManagerRegistry $doctrine;

    public function __construct(
        ProductShoppingListsDataProvider $productShoppingListsDataProvider,
        ManagerRegistry $doctrine
    ) {
        $this->productShoppingListsDataProvider = $productShoppingListsDataProvider;
        $this->doctrine = $doctrine;
    }

    public function onPreBuild(PreBuild $event): void
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_SHOPPING_LISTS => [
                    'type'          => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ]
            ]
        );
    }

    public function onResultAfter(SearchResultAfter $event): void
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $products = [];
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(Product::class);
        foreach ($records as $record) {
            $product = $em->getReference(Product::class, $record->getValue('id'));
            if ($product) {
                $products[] = $product;
            }
        }

        $groupedShoppingLists = $this->productShoppingListsDataProvider->getProductsUnitsQuantity($products);
        if (!$groupedShoppingLists) {
            return;
        }

        foreach ($records as $record) {
            $productId = $record->getValue('id');
            if (\array_key_exists($productId, $groupedShoppingLists)) {
                $record->addData([self::COLUMN_SHOPPING_LISTS => $groupedShoppingLists[$productId]]);
            }
        }
    }
}
