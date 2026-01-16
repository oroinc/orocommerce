<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;

class LoadShoppingListLineItems extends AbstractShoppingListLineItemsFixture
{
    public const LINE_ITEM_1 = 'shopping_list_line_item.1';
    public const LINE_ITEM_2 = 'shopping_list_line_item.2';
    public const LINE_ITEM_3 = 'shopping_list_line_item.3';
    public const LINE_ITEM_4 = 'shopping_list_line_item.4';
    public const LINE_ITEM_5 = 'shopping_list_line_item.5';
    public const LINE_ITEM_7 = 'shopping_list_line_item.7';
    public const LINE_ITEM_8 = 'shopping_list_line_item.8';
    public const LINE_ITEM_9 = 'shopping_list_line_item.9';
    public const LINE_ITEM_10 = 'shopping_list_lin_item.10';
    public const LINE_ITEM_11 = 'shopping_list_lin_item.11';
    public const SAVED_FOR_LATER_LINE_ITEM_1 = 'saved_for_later_lin_item.1';

    protected static array $lineItems = [
        self::LINE_ITEM_1 => [
            'product' => LoadProductData::PRODUCT_1,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
            'unit' => 'product_unit.bottle',
            'quantity' => 23.15
        ],
        self::LINE_ITEM_2 => [
            'product' => LoadProductData::PRODUCT_4,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_3,
            'unit' => 'product_unit.bottle',
            'quantity' => 5
        ],
        self::LINE_ITEM_3 => [
            'product' => LoadProductData::PRODUCT_5,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_4,
            'unit' => 'product_unit.bottle',
            'quantity' => 1
        ],
        self::LINE_ITEM_4 => [
            'product' => LoadProductData::PRODUCT_1,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
            'unit' => 'product_unit.box',
            'quantity' => 1
        ],
        self::LINE_ITEM_5 => [
            'product' => LoadProductData::PRODUCT_5,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
            'unit' => 'product_unit.bottle',
            'quantity' => 1
        ],
        self::LINE_ITEM_7 => [
            'product' => LoadProductData::PRODUCT_7,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_7,
            'unit' => 'product_unit.bottle',
            'quantity' => 7
        ],
        self::LINE_ITEM_8 => [
            'product' => LoadProductData::PRODUCT_1,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_8,
            'unit' => 'product_unit.bottle',
            'quantity' => 8
        ],
        self::LINE_ITEM_9 => [
            'product' => LoadProductData::PRODUCT_4,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_6,
            'unit' => 'product_unit.bottle',
            'quantity' => 3
        ],
        self::LINE_ITEM_10 => [
            'product' => LoadProductData::PRODUCT_3,
            'parentProduct' => LoadProductData::PRODUCT_8,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
            'unit' => 'product_unit.milliliter',
            'quantity' => 3
        ],
        self::LINE_ITEM_11 => [
            'product' => LoadProductData::PRODUCT_4,
            'parentProduct' => LoadProductData::PRODUCT_8,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
            'unit' => 'product_unit.milliliter',
            'quantity' => 4
        ],
        self::SAVED_FOR_LATER_LINE_ITEM_1 => [
            'product' => LoadProductData::PRODUCT_1,
            'savedForLaterList' => LoadShoppingLists::SHOPPING_LIST_3,
            'unit' => 'product_unit.bottle',
            'quantity' => 5
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(parent::getDependencies(), [
            LoadProductUnitPrecisions::class,
            LoadShoppingLists::class
        ]);
    }
}
