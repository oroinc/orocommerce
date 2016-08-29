<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Entity;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PriceListToProductTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new PriceListToProduct(), [
            ['id', 42],
            ['manual', 1],
            ['product',  new Product()],
            ['priceList',  new PriceList()],
        ]);
    }
}
