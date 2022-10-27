<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\ProductLowInventoryThresholdFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\ProductStub;

class ProductLowInventoryThresholdFormExtensionTest extends ProductInventoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productFormExtension = new ProductLowInventoryThresholdFormExtension();
    }

    /**
     * {@inheritdoc}
     */
    protected function assertProductFallBack(ProductStub $product, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $product->getLowInventoryThreshold());
        $this->assertEquals(
            $expectedFallBackId,
            $product->getLowInventoryThreshold()->getFallback()
        );
    }
}
