<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\InventoryBundle\Form\Extension\CategoryQuantityToOrderFormExtension;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;
use Symfony\Component\Form\FormBuilderInterface;

class CategoryQuantityToOrderFormExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CategoryQuantityToOrderFormExtension
     */
    protected $categoryFormExtension;

    /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $builder;

    /** @var CategoryStub */
    protected $category;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->categoryFormExtension = new CategoryQuantityToOrderFormExtension();
        $this->builder = $this->createMock(FormBuilderInterface::class);
        $this->category = new CategoryStub();
        $this->builder
            ->expects($this->once())
            ->method('getData')
            ->willReturn($this->category);

        $this->builder
            ->expects($this->exactly(2))
            ->method('add')
            ->willReturnSelf();
    }

    public function testBuildFormWithEmptyFallbackProperties()
    {
        $options = [];
        $this->categoryFormExtension->buildForm($this->builder, $options);

        $this->assertInstanceOf(
            EntityFieldFallbackValue::class,
            $this->category->getMinimumQuantityToOrder()
        );
        $this->assertInstanceOf(
            EntityFieldFallbackValue::class,
            $this->category->getMaximumQuantityToOrder()
        );
        $this->assertEquals(
            SystemConfigFallbackProvider::FALLBACK_ID,
            $this->category->getMinimumQuantityToOrder()->getFallback()
        );
        $this->assertEquals(
            SystemConfigFallbackProvider::FALLBACK_ID,
            $this->category->getMaximumQuantityToOrder()->getFallback()
        );
    }

    public function testBuildFormWithEmptyFallbackPropertiesWithParentCategory()
    {
        $this->category->setParentCategory(new CategoryStub());

        $options = [];
        $this->categoryFormExtension->buildForm($this->builder, $options);

        $this->assertInstanceOf(
            EntityFieldFallbackValue::class,
            $this->category->getMinimumQuantityToOrder()
        );
        $this->assertInstanceOf(
            EntityFieldFallbackValue::class,
            $this->category->getMaximumQuantityToOrder()
        );
        $this->assertEquals(
            ParentCategoryFallbackProvider::FALLBACK_ID,
            $this->category->getMinimumQuantityToOrder()->getFallback()
        );
        $this->assertEquals(
            ParentCategoryFallbackProvider::FALLBACK_ID,
            $this->category->getMaximumQuantityToOrder()->getFallback()
        );
    }

    public function testBuildFormWithNonEmptyFallbackProperties()
    {
        $minQuantityToOrder = new EntityFieldFallbackValue();
        $minQuantityToOrder->setScalarValue(5);

        $maxQuantityToOrder = new EntityFieldFallbackValue();
        $maxQuantityToOrder->setScalarValue(11);

        $this->category
            ->setMinimumQuantityToOrder($minQuantityToOrder)
            ->setMaximumQuantityToOrder($maxQuantityToOrder);

        $options = [];
        $this->categoryFormExtension->buildForm($this->builder, $options);

        $this->assertEquals(
            $minQuantityToOrder,
            $this->category->getMinimumQuantityToOrder()
        );
        $this->assertEquals(
            $maxQuantityToOrder,
            $this->category->getMaximumQuantityToOrder()
        );
    }
}
