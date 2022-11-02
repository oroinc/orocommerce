<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

class LoadValidAndInvalidCheckoutSubtotals extends LoadCheckoutSubtotals
{
    /** @var array */
    protected static $data = [
        self::CHECKOUT_SUBTOTAL_1 => [
            'checkout' => LoadShoppingListsCheckoutsData::CHECKOUT_1,
            'currency' => 'USD',
            'amount' => 500,
            'valid' => true,
        ],
        self::CHECKOUT_SUBTOTAL_2 => [
            'checkout' => LoadShoppingListsCheckoutsData::CHECKOUT_2,
            'currency' => 'USD',
            'amount' => 300,
            'valid' => true,
        ],
        self::CHECKOUT_SUBTOTAL_3 => [
            'checkout' => LoadShoppingListsCheckoutsData::CHECKOUT_3,
            'currency' => 'USD',
            'combinedPriceList' => '1f',
            'amount' => 100,
            'valid' => true,
        ],
        self::CHECKOUT_SUBTOTAL_4 => [
            'checkout' => LoadShoppingListsCheckoutsData::CHECKOUT_4,
            'currency' => 'USD',
            'amount' => 300,
            'valid' => true,
        ],
        self::CHECKOUT_SUBTOTAL_7 => [
            'checkout' => LoadShoppingListsCheckoutsData::CHECKOUT_7,
            'currency' => 'USD',
            'amount' => 200,
            'priceList' => 'price_list_1',
            'valid' => false,
        ],
        self::CHECKOUT_SUBTOTAL_8 => [
            'checkout' => LoadShoppingListsCheckoutsData::CHECKOUT_8,
            'currency' => 'USD',
            'amount' => 200,
            'priceList' => 'price_list_1',
            'valid' => false,
        ],
        self::CHECKOUT_SUBTOTAL_9 => [
            'checkout' => LoadShoppingListsCheckoutsData::CHECKOUT_9,
            'currency' => 'USD',
            'combinedPriceList' => '1f',
            'amount' => 200,
            'valid' => true,
        ],
    ];
}
