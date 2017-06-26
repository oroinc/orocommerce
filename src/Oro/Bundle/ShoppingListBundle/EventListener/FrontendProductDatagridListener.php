<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
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

    /**
     * FrontendProductDatagridListener constructor.
     * @param ProductShoppingListsDataProvider $productShoppingListsDataProvider
     */
    public function __construct(ProductShoppingListsDataProvider $productShoppingListsDataProvider)
    {
        $this->productShoppingListsDataProvider = $productShoppingListsDataProvider;
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
                self::COLUMN_LINE_ITEMS => [
                    'type'          => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY,
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
        $productIds = [];

        foreach ($records as $record) {
            $productIds[] = $record->getValue('id');
        }

        $groupedShoppingLists = $this->productShoppingListsDataProvider->getProductsUnitsQuantity($productIds);

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
