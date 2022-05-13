<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

class LoadDuplicateCombinedProductPrices extends LoadCombinedProductPrices
{
    /**
     * @var array
     */
    protected static $data = [
        [
            'product' => 'product-1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 10.0,
            'currency' => 'USD',
            'reference' => 'cpl_price.1'
        ],
        [
            'product' => 'product-1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 10.0,
            'currency' => 'USD',
            'reference' => 'cpl_price.1.1'
        ],
        [
            'product' => 'product-1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 10.0,
            'currency' => 'USD',
            'reference' => 'cpl_price.1.2'
        ],
        [
            'product' => 'product-2',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 10.0,
            'currency' => 'USD',
            'reference' => 'cpl_price.2'
        ],
        [
            'product' => 'product-1',
            'priceList' => '2f',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 10.0,
            'currency' => 'USD',
            'reference' => 'cpl_price.3'
        ]
    ];
}
