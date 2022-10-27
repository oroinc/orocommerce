<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadConfigurableProductWithVariants;

class LoadShoppingListConfigurableLineItems extends AbstractShoppingListLineItemsFixture
{
    public const LINE_ITEM_1 = 'shopping_list_configurable_line_item.1';
    private const LINE_ITEM_2 = 'shopping_list_configurable_line_item.2';
    private const LINE_ITEM_3 = 'shopping_list_configurable_line_item.3';
    private const LINE_ITEM_4 = 'shopping_list_configurable_line_item.4';
    private const LINE_ITEM_5 = 'shopping_list_configurable_line_item.5';

    /** @var array */
    protected static $lineItems = [
        self::LINE_ITEM_1 => [
            'product' => LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_2,
            'unit' => 'product_unit.bottle',
            'quantity' => 23.15
        ],
        self::LINE_ITEM_2 => [
            'product' => LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_9,
            'unit' => 'product_unit.bottle',
            'quantity' => 5
        ],
        self::LINE_ITEM_3 => [
            'product' => LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
            'parentProduct' => LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_9,
            'unit' => 'product_unit.bottle',
            'quantity' => 1
        ],
        self::LINE_ITEM_4 => [
            'product' => LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
            'parentProduct' => LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
            'unit' => 'product_unit.box',
            'quantity' => 1
        ],
        self::LINE_ITEM_5 => [
            'product' => LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
            'parentProduct' => LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
            'unit' => 'product_unit.box',
            'quantity' => 1
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadConfigurableProductWithVariants::class,
            LoadShoppingLists::class,
        ];
    }
}
