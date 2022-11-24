<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Model;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Model\CategoryMaterializedPathModifier;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\ReflectionUtil;

class CategoryMaterializedPathModifierTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtraActionEntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var CategoryMaterializedPathModifier */
    private $modifier;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->repository = $this->createMock(CategoryRepository::class);

        $this->modifier = new CategoryMaterializedPathModifier($this->doctrineHelper);
    }

    public function testCalculateMaterializedPath()
    {
        $parent = new Category();
        $parent->setMaterializedPath('1_2');
        ReflectionUtil::setId($parent, 2);

        $category = new Category();
        $category->setParentCategory($parent);
        ReflectionUtil::setId($category, 3);

        $this->repository->expects(self::once())
            ->method('updateMaterializedPath');
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->repository);
        $this->modifier->calculateMaterializedPath($category, true);

        $this->assertNotNull($category->getMaterializedPath());
        $this->assertEquals('1_2_3', $category->getMaterializedPath());
    }

    public function testUpdateMaterializedPathNested()
    {
        $parent = new Category();
        ReflectionUtil::setId($parent, 1);

        $category = new Category();
        $category->setParentCategory($parent);
        ReflectionUtil::setId($category, 2);

        $children = [$category];

        $this->repository->expects(self::exactly(count($children)))
            ->method('updateMaterializedPath');
        $this->doctrineHelper->expects(self::exactly(count($children)))
            ->method('getEntityRepositoryForClass')
            ->willReturn($this->repository);

        $this->modifier->updateMaterializedPathNested($parent, $children);

        $this->assertNotNull($parent->getMaterializedPath());
        $this->assertEquals('1', $parent->getMaterializedPath());

        $this->assertNotNull($category->getMaterializedPath());
        $this->assertEquals('1_2', $category->getMaterializedPath());
    }
}
