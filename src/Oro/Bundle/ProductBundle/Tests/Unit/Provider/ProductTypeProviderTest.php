<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;

class ProductTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductTypeProvider */
    protected $productTypeProvider;

    protected function setUp(): void
    {
        $this->productTypeProvider = new ProductTypeProvider();
    }

    public function testGetAvailableProductTypes()
    {
        $expected = [
            'oro.product.type.simple' => Product::TYPE_SIMPLE,
            'oro.product.type.configurable' => Product::TYPE_CONFIGURABLE,
        ];

        $this->assertEquals($expected, $this->productTypeProvider->getAvailableProductTypes());
    }
}
