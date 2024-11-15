<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;

class LoadGuestShoppingListLineItems extends AbstractShoppingListLineItemsFixture
{
    public const LINE_ITEM_1 = 'guest_shopping_list_line_item.1';
    public const LINE_ITEM_2 = 'guest_shopping_list_line_item.2';

    protected static array $lineItems = [
        self::LINE_ITEM_1 => [
            'product' => LoadProductData::PRODUCT_4,
            'shoppingList' => LoadGuestShoppingLists::GUEST_SHOPPING_LIST_1,
            'unit' => 'product_unit.bottle',
            'quantity' => 5
        ],
        self::LINE_ITEM_2 => [
            'product' => LoadProductData::PRODUCT_5,
            'shoppingList' => LoadGuestShoppingLists::GUEST_SHOPPING_LIST_2,
            'unit' => 'product_unit.bottle',
            'quantity' => 1
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(parent::getDependencies(), [
            LoadProductUnitPrecisions::class,
            LoadGuestShoppingLists::class,
        ]);
    }
}
