<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\CategoryInventoryBackOrderFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;

class CategoryInventoryBackOrderFormExtensionTest extends CategoryInventoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryFormExtension = new CategoryInventoryBackOrderFormExtension();
    }

    public function testBuildFormWithNonEmptyFallbackProperty()
    {
        $backOrderFallbackValue = new EntityFieldFallbackValue();
        $backOrderFallbackValue->setScalarValue(true);

        $category = $this->getCategory();
        $category->setBackOrder($backOrderFallbackValue);

        $this->builder
            ->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $options = [];
        $this->categoryFormExtension->buildForm($this->builder, $options);
        $this->assertEquals(
            $backOrderFallbackValue,
            $category->getBackOrder()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertCategoryFallBack(CategoryStub $category, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $category->getBackOrder());

        $this->assertEquals(
            $expectedFallBackId,
            $category->getBackOrder()->getFallback()
        );
    }
}
