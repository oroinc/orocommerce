<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Entity;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToProduct;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PriceListToProductTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new PriceListToProduct(), [
            ['id', 42],
            ['priceList', new PriceList()],
            ['product', new Product()],
            ['manual', 1]
        ]);
    }
}
