<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryTreeTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AbstractTreeHandler
     */
    protected $treeHandler;

    /**
     * @var CategoryTreeType
     */
    protected $type;

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMockBuilder(OptionsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'class' => Category::class,
                    'tree_key' => 'commerce-category',
                    'tree_data' => [$this->treeHandler, 'createTree']
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryTreeType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityTreeSelectType::class, $this->type->getParent());
    }

    protected function setUp()
    {
        $this->treeHandler = $this->getMockBuilder(AbstractTreeHandler::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->type = new CategoryTreeType($this->treeHandler);
    }
}
