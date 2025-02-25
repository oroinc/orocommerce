<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

class LoadCombinedProductPricesWithDuplicates extends LoadCombinedProductPrices
{
    protected static $data = [
        [
            'product' => 'product-1',
            'priceList' => '2t_3t',
            'qty' => 10,
            'unit' => 'product_unit.liter',
            'price' => 1.01,
            'currency' => 'USD',
            'reference' => 'product_price.26'
        ],
        //duplicate
        [
            'product' => 'product-1',
            'priceList' => '2t_3t',
            'qty' => 10,
            'unit' => 'product_unit.liter',
            'price' => 1.01,
            'currency' => 'USD',
            'reference' => 'product_price.27'
        ],
    ];
}
