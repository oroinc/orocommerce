<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\CategoryInventoryThresholdFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;

class CategoryInventoryThresholdFormExtensionTest extends CategoryInventoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryFormExtension = new CategoryInventoryThresholdFormExtension();
    }

    public function testBuildFormWithNonEmptyFallbackProperty()
    {
        $inventoryThresholdFallbackValue = new EntityFieldFallbackValue();
        $inventoryThresholdFallbackValue->setScalarValue(12);

        $category = $this->getCategory();
        $category->setInventoryThreshold($inventoryThresholdFallbackValue);

        $this->builder
            ->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $options = [];
        $this->categoryFormExtension->buildForm($this->builder, $options);
        $this->assertEquals(
            $inventoryThresholdFallbackValue,
            $category->getInventoryThreshold()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertCategoryFallBack(CategoryStub $category, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $category->getInventoryThreshold());

        $this->assertEquals(
            $expectedFallBackId,
            $category->getInventoryThreshold()->getFallback()
        );
    }
}
