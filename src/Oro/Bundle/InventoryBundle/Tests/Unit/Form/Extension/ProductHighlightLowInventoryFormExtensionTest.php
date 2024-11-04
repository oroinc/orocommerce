<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\ProductHighlightLowInventoryFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\ProductStub;

class ProductHighlightLowInventoryFormExtensionTest extends ProductInventoryTest
{
    #[\Override]
    protected function setUp(): void
    {
        $this->productFormExtension = new ProductHighlightLowInventoryFormExtension();
    }

    #[\Override]
    protected function assertProductFallBack(ProductStub $product, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $product->getHighlightLowInventory());
        $this->assertEquals(
            $expectedFallBackId,
            $product->getHighlightLowInventory()->getFallback()
        );
    }
}
