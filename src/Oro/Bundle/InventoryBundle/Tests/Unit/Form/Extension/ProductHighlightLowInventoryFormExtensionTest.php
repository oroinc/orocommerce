<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\ProductHighlightLowInventoryFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\ProductStub;

class ProductHighlightLowInventoryFormExtensionTest extends ProductInventoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productFormExtension = new ProductHighlightLowInventoryFormExtension();
    }

    /**
     * {@inheritdoc}
     */
    protected function assertProductFallBack(ProductStub $product, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $product->getHighlightLowInventory());
        $this->assertEquals(
            $expectedFallBackId,
            $product->getHighlightLowInventory()->getFallback()
        );
    }
}
