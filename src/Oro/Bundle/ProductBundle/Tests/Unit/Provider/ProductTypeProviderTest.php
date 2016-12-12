<?php

namespace Oro\Bundle\ProductBundle\Tests\UnitProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;

class ProductTypeProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductTypeProvider */
    protected $productTypeProvider;

    protected function setup()
    {
        $this->productTypeProvider = new ProductTypeProvider();
    }

    public function testGetAvailableProductTypes()
    {
        $expected = [
            Product::TYPE_SIMPLE => 'oro.product.type.simple',
            Product::TYPE_CONFIGURABLE => 'oro.product.type.configurable'
        ];

        $this->assertEquals($expected, $this->productTypeProvider->getAvailableProductTypes());
    }
}
