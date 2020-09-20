<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

class LoadShoppingListConfigurableLineItems extends AbstractShoppingListLineItemsFixture
{
    use UserUtilityTrait;

    public const LINE_ITEM_1 = 'shopping_list_configurable_line_item.1';

    /** @var array */
    protected static $lineItems = [
        self::LINE_ITEM_1 => [
            'product' => LoadProductData::PRODUCT_8,
            'shoppingList' => LoadShoppingLists::SHOPPING_LIST_5,
            'unit' => 'product_unit.box',
            'quantity' => 0
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadProductUnitPrecisions::class,
            LoadShoppingLists::class,
        ];
    }
}
