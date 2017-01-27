<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductVariantProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;

class ProductVariantProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $availabilityProvider;

    /** @var ProductVariantProvider */
    private $productVariantProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->availabilityProvider = $this->getMockBuilder(ProductVariantAvailabilityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productVariantProvider = new ProductVariantProvider($this->availabilityProvider);
    }

    public function testHasProductAnyAvailableVariantReturnFalse()
    {
        $product = new Product();
        $this->availabilityProvider->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([]);

        $result = $this->productVariantProvider->hasProductAnyAvailableVariant($product);
        $this->assertFalse($result);
    }

    public function testHasProductAnyAvailableVariantReturnTrue()
    {
        $product = new Product();
        $this->availabilityProvider->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn([new Product()]);

        $result = $this->productVariantProvider->hasProductAnyAvailableVariant($product);
        $this->assertTrue($result);
    }
}
