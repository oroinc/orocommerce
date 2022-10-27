<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\ProductManageInventoryFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\ProductStub;

class ProductManageInventoryFormExtensionTest extends ProductInventoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->productFormExtension = new ProductManageInventoryFormExtension();
    }

    /**
     * {@inheritdoc}
     */
    protected function assertProductFallBack(ProductStub $product, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $product->getManageInventory());
        $this->assertEquals(
            $expectedFallBackId,
            $product->getManageInventory()->getFallback()
        );
    }
}
