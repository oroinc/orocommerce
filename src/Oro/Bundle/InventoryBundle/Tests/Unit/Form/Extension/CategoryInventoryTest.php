<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\InventoryBundle\Tests\Unit\Form\Extension\Stub\CategoryStub;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

abstract class CategoryInventoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AbstractTypeExtension
     */
    protected $categoryFormExtension;

    /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $builder;

    /** @var CategoryStub */
    protected $category;

    /**
     * @param CategoryStub $category
     * @param string $expectedFallBackId
     *
     * @return mixed
     */
    abstract protected function assertCategoryFallBack(CategoryStub $category, $expectedFallBackId);

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->builder = $this->createMock(FormBuilderInterface::class);
        $this->builder
            ->expects($this->exactly(1))
            ->method('add')
            ->willReturnSelf();
    }

    /**
     * @dataProvider providerTestBuildForm
     *
     * @param CategoryStub $category
     * @param string       $expectedFallBackId
     */
    public function testBuildForm(CategoryStub $category, $expectedFallBackId)
    {
        $this->builder
            ->expects($this->once())
            ->method('getData')
            ->willReturn($category);

        $options = [];
        $this->categoryFormExtension->buildForm($this->builder, $options);
        $this->assertCategoryFallBack($category, $expectedFallBackId);
    }

    /**
     * @return array
     */
    public function providerTestBuildForm()
    {
        return [
            'category without parent category' => [
                'category' => $this->getCategory(),
                'expectedFallBackId' => SystemConfigFallbackProvider::FALLBACK_ID,
            ],
            'category with parent category' => [
                'category' => $this->getCategoryWithParentCategory(),
                'expectedFallBackId' => ParentCategoryFallbackProvider::FALLBACK_ID
            ],
        ];
    }

    /**
     * @return CategoryStub
     */
    protected function getCategory()
    {
        return new CategoryStub();
    }

    /**
     * @return CategoryStub
     */
    protected function getCategoryWithParentCategory()
    {
        $category = new CategoryStub();
        $parentCategory = new CategoryStub();

        $category->setParentCategory($parentCategory);

        return $category;
    }
}
