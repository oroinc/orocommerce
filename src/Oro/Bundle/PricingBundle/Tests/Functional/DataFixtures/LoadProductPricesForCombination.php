<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

class LoadProductPricesForCombination extends LoadProductPrices
{
    /**
     * @var array
     */
    public static $data = [
        'product_price.1' => [
            'priceList' => 'price_list_1',
            'product' => 'product-1',
            'value' => 1,
            'currency' => 'USD',
            'quantity' => 1,
            'unit' => 'product_unit.liter',
        ],
        'product_price.2' => [
            'priceList' => 'price_list_1',
            'product' => 'product-1',
            'value' => 10,
            'currency' => 'USD',
            'quantity' => 9,
            'unit' => 'product_unit.liter',
        ],
        'product_price.3' => [
            'priceList' => 'price_list_1',
            'product' => 'product-2',
            'value' => 1,
            'currency' => 'USD',
            'quantity' => 1,
            'unit' => 'product_unit.bottle',
        ],
        'product_price.4' => [
            'priceList' => 'price_list_2',
            'product' => 'product-1',
            'value' => 2,
            'currency' => 'USD',
            'quantity' => 1,
            'unit' => 'product_unit.liter',
        ],
        'product_price.5' => [
            'priceList' => 'price_list_2',
            'product' => 'product-1',
            'value' => 3,
            'currency' => 'USD',
            'quantity' => 1,
            'unit' => 'product_unit.bottle',
        ],
        'product_price.6' => [
            'priceList' => 'price_list_3',
            'product' => 'product-1',
            'value' => 15,
            'currency' => 'USD',
            'quantity' => 10,
            'unit' => 'product_unit.liter',
        ],
        'product_price.7' => [
            'priceList' => 'price_list_3',
            'product' => 'product-2',
            'value' => 10,
            'currency' => 'USD',
            'quantity' => 10,
            'unit' => 'product_unit.bottle',
        ],
        'product_price.8' => [
            'priceList' => 'price_list_2',
            'product' => 'product-1',
            'value' => 2,
            'currency' => 'EUR',
            'quantity' => 1,
            'unit' => 'product_unit.liter',
        ],
    ];
}
