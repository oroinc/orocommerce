<?php

namespace Oro\Bundle\ProductBundle\Tests\Layout\Block\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Layout\Block\Type\BreadcrumbsNavigationBlockType;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
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

    /**
     * {@inheritdoc}
     */
    public function testBuildingView()
    {
        $parentCategory = $this->getMock(Category::class);
        $parentParentCategory = $this->getMock(Category::class);

        $category = $this->getMock(Category::class);
        $category->method('getParentCategory')
            ->willReturn($parentCategory);

        $parentCategory->method('getParentCategory')
            ->willReturn($parentParentCategory);

        $blockView = $this->getMock(BlockView::class);

        $blockInterface = $this->getMock(BlockInterface::class);

        $options = ['currentCategory' => $category];

        $this->blockType->buildView($blockView, $blockInterface, $options);

        $this->assertSame([$parentParentCategory, $parentCategory], $blockView->vars['parentCategories']);
    }

    public function testGetName()
    {
        $this->assertEquals('product_search_navigation', $this->blockType->getName());
    }
}
