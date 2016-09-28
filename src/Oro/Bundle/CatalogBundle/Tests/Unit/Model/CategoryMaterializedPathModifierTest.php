<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Model;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Model\CategoryMaterializedPathModifier;

class CategoryMaterializedPathModifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryMaterializedPathModifier
     */
    protected $modifier;

    /**
     * @var ExtraActionEntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->storage = $this->getMockBuilder(ExtraActionEntityStorageInterface::class)
            ->getMock();

        $this->modifier = new CategoryMaterializedPathModifier($this->storage);
    }

    public function testCalculateMaterializedPath()
    {
        $reflection = new \ReflectionProperty(Category::class, 'id');
        $reflection->setAccessible(true);

        $parent = new Category();
        $parent->setMaterializedPath('1_2');
        $reflection->setValue($parent, 2);

        $category = new Category();
        $category->setParentCategory($parent);
        $reflection->setValue($category, 3);

        $this->storage->expects($this->once())
            ->method('scheduleForExtraInsert');

        $this->modifier->calculateMaterializedPath($category, true);

        $this->assertNotNull($category->getMaterializedPath());
        $this->assertEquals('1_2_3', $category->getMaterializedPath());
    }

    public function testUpdateMaterializedPathNested()
    {
        $reflection = new \ReflectionProperty(Category::class, 'id');
        $reflection->setAccessible(true);

        $parent = new Category();
        $reflection->setValue($parent, 1);

        $category = new Category();
        $category->setParentCategory($parent);
        $reflection->setValue($category, 2);

        $children = [$category];
        $this->storage->expects($this->exactly(count($children)))
            ->method('scheduleForExtraInsert');

        $this->modifier->updateMaterializedPathNested($parent, $children);

        $this->assertNotNull($parent->getMaterializedPath());
        $this->assertEquals('1', $parent->getMaterializedPath());

        $this->assertNotNull($category->getMaterializedPath());
        $this->assertEquals('1_2', $category->getMaterializedPath());
    }
}
