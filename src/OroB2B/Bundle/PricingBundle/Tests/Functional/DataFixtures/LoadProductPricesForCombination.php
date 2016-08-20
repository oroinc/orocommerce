<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

class LoadProductPricesForCombination extends LoadProductPrices
{
    /**
     * @var array
     */
    protected $data = [
        [
            'priceList' => 'price_list_1',
            'product' => 'product.1',
            'price' => 1,
            'currency' => 'USD',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'reference' => 'product_price.1'
        ],
        [
            'priceList' => 'price_list_1',
            'product' => 'product.1',
            'price' => 10,
            'currency' => 'USD',
            'qty' => 9,
            'unit' => 'product_unit.liter',
            'reference' => 'product_price.2'
        ],
        [
            'priceList' => 'price_list_1',
            'product' => 'product.2',
            'price' => 1,
            'currency' => 'USD',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'reference' => 'product_price.3'
        ],
        [
            'priceList' => 'price_list_2',
            'product' => 'product.1',
            'price' => 2,
            'currency' => 'USD',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'reference' => 'product_price.4'
        ],
        [
            'priceList' => 'price_list_2',
            'product' => 'product.1',
            'price' => 3,
            'currency' => 'USD',
            'qty' => 1,
            'unit' => 'product_unit.bottle',
            'reference' => 'product_price.5'
        ],
        [
            'priceList' => 'price_list_3',
            'product' => 'product.1',
            'price' => 15,
            'currency' => 'USD',
            'qty' => 10,
            'unit' => 'product_unit.liter',
            'reference' => 'product_price.6'
        ],
        [
            'priceList' => 'price_list_3',
            'product' => 'product.2',
            'price' => 10,
            'currency' => 'USD',
            'qty' => 10,
            'unit' => 'product_unit.bottle',
            'reference' => 'product_price.7'
        ],
        [
            'priceList' => 'price_list_2',
            'product' => 'product.1',
            'price' => 2,
            'currency' => 'EUR',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'reference' => 'product_price.8'
        ],
    ];
}
