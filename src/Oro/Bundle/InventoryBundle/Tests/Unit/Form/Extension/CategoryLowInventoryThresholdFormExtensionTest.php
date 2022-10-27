<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\CategoryLowInventoryThresholdFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;

class CategoryLowInventoryThresholdFormExtensionTest extends CategoryInventoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryFormExtension = new CategoryLowInventoryThresholdFormExtension();
    }

    public function testBuildFormWithNonEmptyFallbackProperty()
    {
        $lowInventoryThresholdFallbackValue = new EntityFieldFallbackValue();
        $lowInventoryThresholdFallbackValue->setScalarValue(12);

        $category = $this->getCategory();
        $category->setLowInventoryThreshold($lowInventoryThresholdFallbackValue);

        $this->builder
            ->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $options = [];
        $this->categoryFormExtension->buildForm($this->builder, $options);
        $this->assertEquals(
            $lowInventoryThresholdFallbackValue,
            $category->getLowInventoryThreshold()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertCategoryFallBack(CategoryStub $category, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $category->getLowInventoryThreshold());

        $this->assertEquals(
            $expectedFallBackId,
            $category->getLowInventoryThreshold()->getFallback()
        );
    }
}
