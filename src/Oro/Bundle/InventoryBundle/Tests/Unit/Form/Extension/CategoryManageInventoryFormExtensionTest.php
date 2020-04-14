<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\Form\Extension\CategoryManageInventoryFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;

class CategoryManageInventoryFormExtensionTest extends CategoryInventoryTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryFormExtension = new CategoryManageInventoryFormExtension();
    }

    public function testBuildFormWithNonEmptyFallbackProperty()
    {
        $manageInventoryFallbackValue = new EntityFieldFallbackValue();
        $manageInventoryFallbackValue->setScalarValue(false);

        $category = $this->getCategory();
        $category->setManageInventory($manageInventoryFallbackValue);

        $this->builder
            ->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $options = [];
        $this->categoryFormExtension->buildForm($this->builder, $options);
        $this->assertEquals(
            $manageInventoryFallbackValue,
            $category->getManageInventory()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function assertCategoryFallBack(CategoryStub $category, $expectedFallBackId)
    {
        $this->assertInstanceOf(EntityFieldFallbackValue::class, $category->getManageInventory());

        $this->assertEquals(
            $expectedFallBackId,
            $category->getManageInventory()->getFallback()
        );
    }
}
