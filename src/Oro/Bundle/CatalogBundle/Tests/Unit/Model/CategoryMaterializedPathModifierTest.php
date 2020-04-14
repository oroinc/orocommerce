<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Model;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Model\CategoryMaterializedPathModifier;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class CategoryMaterializedPathModifierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CategoryMaterializedPathModifier
     */
    protected $modifier;

    /**
     * @var ExtraActionEntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $doctrineHelper;

    /**
     * @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $repository;
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = static::createMock(CategoryRepository::class);

        $this->modifier = new CategoryMaterializedPathModifier($this->doctrineHelper);
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

        $this->repository->expects(static::once())->method('updateMaterializedPath');
        $this->doctrineHelper->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->repository);
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

        $this->repository->expects(static::exactly(count($children)))
            ->method('updateMaterializedPath');
        $this->doctrineHelper->expects(static::exactly(count($children)))
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->repository);

        $this->modifier->updateMaterializedPathNested($parent, $children);

        $this->assertNotNull($parent->getMaterializedPath());
        $this->assertEquals('1', $parent->getMaterializedPath());

        $this->assertNotNull($category->getMaterializedPath());
        $this->assertEquals('1_2', $category->getMaterializedPath());
    }
}
