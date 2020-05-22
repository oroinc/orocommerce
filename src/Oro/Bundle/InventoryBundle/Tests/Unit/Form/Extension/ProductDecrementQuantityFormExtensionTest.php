<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\ProductDecrementQuantityFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\ProductStub;

class ProductDecrementQuantityFormExtensionTest extends ProductInventoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productFormExtension = new ProductDecrementQuantityFormExtension();
    }

    /**
     * {@inheritdoc}
     */
    protected function assertProductFallBack(ProductStub $product, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $product->getDecrementQuantity());
        $this->assertEquals(
            $expectedFallBackId,
            $product->getDecrementQuantity()->getFallback()
        );
    }
}
