<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\EventListener\ORM\CategoryListener;

class CategoryListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->listener = new CategoryListener();
    }

    public function testCalculateMaterializedPath()
    {
        $reflection = new \ReflectionProperty(Category::class, 'id');
        $reflection->setAccessible(true);

        $grandparent = new Category();
        $reflection->setValue($grandparent, 1);

        $parent = new Category();
        $parent->setParentCategory($grandparent);
        $reflection->setValue($parent, 2);

        $category = new Category();
        $category->setParentCategory($parent);
        $reflection->setValue($category, 3);

        $this->listener->calculateMaterializedPath($category);
        $this->assertNotNull($category->getMaterializedPath());
        $this->assertEquals('1_2_3', $category->getMaterializedPath());
    }
}
