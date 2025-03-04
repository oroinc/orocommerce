<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\ProductInventoryThresholdFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\ProductStub;

class ProductInventoryThresholdFormExtensionTest extends ProductInventoryTest
{
    #[\Override]
    protected function setUp(): void
    {
        $this->productFormExtension = new ProductInventoryThresholdFormExtension();
    }

    #[\Override]
    protected function assertProductFallBack(ProductStub $product, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $product->getInventoryThreshold());
        $this->assertEquals(
            $expectedFallBackId,
            $product->getInventoryThreshold()->getFallback()
        );
    }
}
