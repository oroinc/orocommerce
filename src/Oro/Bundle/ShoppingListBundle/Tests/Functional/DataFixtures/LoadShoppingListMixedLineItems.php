<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadConfigurableProductWithVariants;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;

class LoadShoppingListMixedLineItems extends LoadShoppingListProductKitLineItems
{
    public const LINE_ITEM_1 = 'shopping_list.line_item.1.product_kit_1';
    public const LINE_ITEM_2 = 'shopping_list.line_item.2.simple_product_1';
    public const LINE_ITEM_3 = 'shopping_list.line_item.3.simple_product_2';
    public const LINE_ITEM_4 = 'shopping_list.line_item.4.configurable_variant_1';
    public const LINE_ITEM_5 = 'shopping_list.line_item.4.configurable_variant_2';
    public const LINE_ITEM_1_KIT_ITEM_1 = 'shopping_list_product_kit_item_line_item.1';

    protected static array $lineItems = [
        self::LINE_ITEM_1 => [
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
            'product' => LoadProductKitData::PRODUCT_KIT_1,
            'unit' => 'product_unit.milliliter',
            'quantity' => 1,
            'kitItemLineItems' => [
                self::LINE_ITEM_1_KIT_ITEM_1 => [
                    'kitItem' => LoadProductKitData::PRODUCT_KIT_1 . '-kit-item-0',
                    'product' => LoadProductData::PRODUCT_1,
                    'quantity' => 11
                ],
            ],
        ],
        self::LINE_ITEM_2 => [
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
            'product' => LoadProductData::PRODUCT_1,
            'unit' => 'product_unit.bottle',
            'quantity' => 23.15,
            'kitItemLineItems' => [],
        ],
        self::LINE_ITEM_3 => [
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
            'product' => LoadProductData::PRODUCT_2,
            'unit' => 'product_unit.bottle',
            'quantity' => 5,
            'kitItemLineItems' => [],
        ],
        self::LINE_ITEM_4 => [
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
            'parentProduct' => LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
            'product' => LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU,
            'unit' => 'product_unit.box',
            'quantity' => 1,
            'kitItemLineItems' => [],
        ],
        self::LINE_ITEM_5 => [
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_1,
            'parentProduct' => LoadConfigurableProductWithVariants::CONFIGURABLE_SKU,
            'product' => LoadConfigurableProductWithVariants::SECOND_VARIANT_SKU,
            'unit' => 'product_unit.box',
            'quantity' => 1,
            'kitItemLineItems' => [],
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadProductUnitPrecisions::class,
            LoadShoppingLists::class,
            LoadProductKitData::class,
            LoadConfigurableProductWithVariants::class,
        ];
    }
}
