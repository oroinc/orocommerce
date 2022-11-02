<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

class LoadCombinedProductAdditionalPrices extends LoadCombinedProductPrices
{
    /**
     * @var array
     */
    protected static $data = [
        [
            'product' => 'product-6',
            'priceList' => '2f_1t_3t',
            'qty' => 1,
            'unit' => 'product_unit.liter',
            'price' => 12.2,
            'currency' => 'USD',
            'reference' => 'product_price.213.6'
        ]
    ];
}
