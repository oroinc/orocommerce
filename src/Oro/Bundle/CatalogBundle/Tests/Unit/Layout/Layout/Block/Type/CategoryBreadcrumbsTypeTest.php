<?php

namespace Oro\Bundle\CatalogBundle\Tests\Layout\Block\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Layout\Block\Type\CategoryBreadcrumbsType;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class CategoryBreadcrumbsTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryBreadcrumbsType
     */
    private $blockType;

    public function setUp()
    {
        $this->blockType = new CategoryBreadcrumbsType();
    }

    public function testBuildView()
    {
        /** @var BlockView $blockView */
        $blockView = $this->getMock(BlockView::class);
        $block = $this->getMock(BlockInterface::class);
        $category = new Category();

        $this->blockType->buildView($blockView, $block, new Options(['currentCategory' => $category]));

        $this->assertArrayHasKey('currentCategory', $blockView->vars);
        $this->assertEquals($category, $blockView->vars['currentCategory']);
    }

    /**
     * {@inheritdoc}
     */
    public function testFinishView()
    {
        $parentParentCategory = $this->getMock(Category::class);

        $parentCategory = $this->getMock(Category::class);
        $parentCategory->method('getParentCategory')
            ->willReturn($parentParentCategory);

        $category = $this->getMock(Category::class);
        $category->method('getParentCategory')
            ->willReturn($parentCategory);

        /** @var BlockView $blockView */
        $blockView = $this->getMock(BlockView::class);
        $blockView->vars['currentCategory'] = $category;
        $block = $this->getMock(BlockInterface::class);

        $this->blockType->finishView($blockView, $block);

        $this->assertSame([$parentParentCategory, $parentCategory], $blockView->vars['parentCategories']);
    }

    /**
     * {@inheritdoc}
     */
    public function testFinishViewNoCategory()
    {
        /** @var BlockView $blockView */
        $blockView = $this->getMock(BlockView::class);
        $blockView->vars['currentCategory'] = null;
        $block = $this->getMock(BlockInterface::class);

        $this->blockType->finishView($blockView, $block);

        $this->assertSame([], $blockView->vars['parentCategories']);
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryBreadcrumbsType::NAME, $this->blockType->getName());
    }

    public function testConfigureOptions()
    {
        $optionsResolver = $this->getMock(OptionsResolver::class);
        $optionsResolver->expects($this->once())
            ->method('setDefaults')
            ->with(['currentCategory' => null]);

        $this->blockType->configureOptions($optionsResolver);
    }
}
