<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryTreeType;
use Oro\Bundle\FormBundle\Form\Type\EntityTreeSelectType;
use Oro\Component\Tree\Handler\AbstractTreeHandler;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryTreeTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractTreeHandler|\PHPUnit\Framework\MockObject\MockObject */
    private $treeHandler;

    /** @var CategoryTreeType */
    private $type;

    protected function setUp(): void
    {
        $this->treeHandler = $this->createMock(AbstractTreeHandler::class);

        $this->type = new CategoryTreeType($this->treeHandler);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'class' => Category::class,
                    'tree_key' => 'commerce-category',
                    'tree_data' => [$this->treeHandler, 'createTreeByMasterCatalogRoot']
                ]
            );

        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(EntityTreeSelectType::class, $this->type->getParent());
    }
}
