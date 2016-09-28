<?php

namespace Oro\Bundle\ProductBundle\Tests\Layout\Block\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Layout\Block\Type\BreadcrumbsNavigationBlockType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class BreadcrumbsNavigationBlockTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BreadcrumbsNavigationBlockType
     */
    private $blockType;

    public function setUp()
    {
        $this->blockType = new BreadcrumbsNavigationBlockType();
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

    public function testGetName()
    {
        $this->assertEquals('product_search_navigation', $this->blockType->getName());
    }
}
