<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

class LoadProductPricesForMinimalStrategy extends LoadProductPrices
{
    /**
     * @var array
     */
    public static $data = [
        'min.product_price.p1_1l_USD_pl1' => [
            'priceList' => 'price_list_1',
            'product' => 'product-1',
            'value' => 11,
            'currency' => 'USD',
            'quantity' => 1,
            'unit' => 'product_unit.liter',
        ],
        'min.product_price.p1_10l_USD_pl1' => [
            'priceList' => 'price_list_1',
            'product' => 'product-1',
            'value' => 6,
            'currency' => 'USD',
            'quantity' => 10,
            'unit' => 'product_unit.liter',
        ],
        'min.product_price.p1_1l_USD_pl2' => [
            'priceList' => 'price_list_2',
            'product' => 'product-1',
            'value' => 10,
            'currency' => 'USD',
            'quantity' => 1,
            'unit' => 'product_unit.liter',
        ],
        'min.product_price.p1_10l_USD_pl2' => [
            'priceList' => 'price_list_2',
            'product' => 'product-1',
            'value' => 7,
            'currency' => 'USD',
            'quantity' => 10,
            'unit' => 'product_unit.liter',
        ],
        'min.product_price.p1_1l_USD_pl3' => [
            'priceList' => 'price_list_3',
            'product' => 'product-1',
            'value' => 10,
            'currency' => 'USD',
            'quantity' => 1,
            'unit' => 'product_unit.liter',
        ],
        'min.product_price.p1_10l_USD_pl3' => [
            'priceList' => 'price_list_3',
            'product' => 'product-1',
            'value' => 7,
            'currency' => 'USD',
            'quantity' => 10,
            'unit' => 'product_unit.liter',
        ],
    ];
}
