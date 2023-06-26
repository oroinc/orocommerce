<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

class LoadProductKitCombinedProductPrices extends LoadCombinedProductPrices
{
    /**
     * @var array
     */
    protected static $data = [
        [
            'product' => 'product-1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.milliliter',
            'price' => 10,
            'currency' => 'USD',
            'reference' => 'product_price.20'
        ],
        [
            'product' => 'product-kit-1',
            'priceList' => '1f',
            'qty' => 1,
            'unit' => 'product_unit.milliliter',
            'price' => 20,
            'currency' => 'USD',
            'reference' => 'product_price.27'
        ],
    ];
}
