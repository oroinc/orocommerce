<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\CategoryInventoryDecrementFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;

class CategoryInventoryDecrementFormExtensionTest extends CategoryInventoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryFormExtension = new CategoryInventoryDecrementFormExtension();
    }

    public function testBuildFormWithNonEmptyFallbackProperty()
    {
        $decrementQuantityFallbackValue = new EntityFieldFallbackValue();
        $decrementQuantityFallbackValue->setScalarValue(5);

        $category = $this->getCategory();
        $category->setDecrementQuantity($decrementQuantityFallbackValue);

        $this->builder
            ->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $options = [];
        $this->categoryFormExtension->buildForm($this->builder, $options);
        $this->assertEquals(
            $decrementQuantityFallbackValue,
            $category->getDecrementQuantity()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertCategoryFallBack(CategoryStub $category, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $category->getDecrementQuantity());

        $this->assertEquals(
            $expectedFallBackId,
            $category->getDecrementQuantity()->getFallback()
        );
    }
}
