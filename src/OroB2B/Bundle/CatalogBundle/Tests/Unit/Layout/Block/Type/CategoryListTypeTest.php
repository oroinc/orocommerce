<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Unit\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\ContainerType;
use Oro\Component\Layout\LayoutFactoryBuilderInterface;
use Oro\Bundle\LayoutBundle\Tests\Unit\BlockTypeTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Layout\Block\Type\CategoryListType;

class CategoryListTypeTest extends BlockTypeTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function initializeLayoutFactoryBuilder(LayoutFactoryBuilderInterface $layoutFactoryBuilder)
    {
        parent::initializeLayoutFactoryBuilder($layoutFactoryBuilder);
        $layoutFactoryBuilder
            ->addType(new CategoryListType());
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @expectedExceptionMessage The required option "categories" is missing.
     */
    public function testBuildViewWithoutCategories()
    {
        $this->getBlockView(CategoryListType::NAME, []);
    }

    public function testBuildView()
    {
        $categories = [new Category(), new Category()];
        $view = $this->getBlockView(
            CategoryListType::NAME,
            ['categories' => $categories]
        );

        $this->assertEquals($categories, $view->vars['categories']);
    }

    public function testGetName()
    {
        $type = $this->getBlockType(CategoryListType::NAME);

        $this->assertSame(CategoryListType::NAME, $type->getName());
    }

    public function testGetParent()
    {
        $type = $this->getBlockType(CategoryListType::NAME);

        $this->assertSame(ContainerType::NAME, $type->getParent());
    }
}
