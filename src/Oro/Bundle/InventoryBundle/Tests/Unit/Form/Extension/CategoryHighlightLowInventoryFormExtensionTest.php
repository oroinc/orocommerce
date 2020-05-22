<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\CategoryHighlightLowInventoryFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;

class CategoryHighlightLowInventoryFormExtensionTest extends CategoryInventoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryFormExtension = new CategoryHighlightLowInventoryFormExtension();
    }

    public function testBuildFormWithNonEmptyFallbackProperty()
    {
        $highlightLowInventoryFallbackValue = new EntityFieldFallbackValue();
        $highlightLowInventoryFallbackValue->setScalarValue(false);

        $category = $this->getCategory();
        $category->setHighlightLowInventory($highlightLowInventoryFallbackValue);

        $this->builder
            ->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $options = [];
        $this->categoryFormExtension->buildForm($this->builder, $options);
        $this->assertEquals(
            $highlightLowInventoryFallbackValue,
            $category->getHighlightLowInventory()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertCategoryFallBack(CategoryStub $category, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $category->getHighlightLowInventory());

        $this->assertEquals(
            $expectedFallBackId,
            $category->getHighlightLowInventory()->getFallback()
        );
    }
}
