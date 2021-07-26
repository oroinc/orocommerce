<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ImportExport\Helper;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\ImportExport\Helper\CategoryImportExportHelper;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category as CategoryStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryImportExportHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var CategoryImportExportHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->helper = new CategoryImportExportHelper($this->doctrine);
    }

    /**
     * @dataProvider getCategoryPathDataProvider
     */
    public function testGetCategoryPath(Category $category, string $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->helper->getCategoryPath($category));
    }

    public function getCategoryPathDataProvider(): array
    {
        return [
            [
                'category' => $category1 = $this->createCategory('category 1'),
                'expectedResult' => 'category 1',
            ],
            [
                'category' => $category123 = $this->createCategory('category 3')
                    ->setParentCategory($this->createCategory('category 2')->setParentCategory($category1)),
                'expectedResult' => 'category 1 / category 2 / category 3',
            ],
            [
                'category' => $this->createCategory('category / with / slashes')->setParentCategory($category1),
                'expectedResult' => 'category 1 / category // with // slashes',
            ],
        ];
    }

    private function createCategory(string $title): CategoryStub
    {
        return (new CategoryStub())->addTitle((new CategoryTitle())->setString($title));
    }

    /**
     * @dataProvider getPersistedCategoryPathDataProvider
     */
    public function testGetPersistedCategoryPath(array $categoryPath, string $expectedResult): void
    {
        $repo = $this->mockGetRepository();

        $repo
            ->expects($this->once())
            ->method('getCategoryPath')
            ->with($category = new Category())
            ->willReturn($categoryPath);

        $this->assertEquals($expectedResult, $this->helper->getPersistedCategoryPath($category));
    }

    /**
     * @return CategoryRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private function mockGetRepository()
    {
        $this->doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager = $this->createMock(EntityManager::class));

        $manager
            ->expects($this->once())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($repo = $this->createMock(CategoryRepository::class));

        return $repo;
    }

    public function getPersistedCategoryPathDataProvider(): array
    {
        return [
            [
                'categoryPath' => ['category 1'],
                'expectedResult' => 'category 1',
            ],
            [
                'categoryPath' => ['category 1', 'category 2', 'category 3'],
                'expectedResult' => 'category 1 / category 2 / category 3',
            ],
            [
                'categoryPath' => ['category 1', 'category / with / slashes'],
                'expectedResult' => 'category 1 / category // with // slashes',
            ],
        ];
    }

    public function testGetRootCategory(): void
    {
        $category = $this->getEntity(Category::class);
        $organization = $this->getEntity(Organization::class);

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects($this->once())
            ->method('getSingleResult')
            ->willReturn($category);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects($this->once())
            ->method('andWhere')
            ->with('category.organization = :organization')
            ->willReturnSelf();
        $queryBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('organization', $organization);
        $queryBuilder
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->mockGetRepository();
        $repo
            ->expects($this->once())
            ->method('getMasterCatalogRootQueryBuilder')
            ->willReturn($queryBuilder);

        $this->assertSame($category, $this->helper->getRootCategory($organization));
    }

    public function testGetMaxLeft(): void
    {
        $repo = $this->mockGetRepository();

        $repo
            ->expects($this->once())
            ->method('getMaxLeft')
            ->willReturn($max = 10);

        $this->assertSame($max, $this->helper->getMaxLeft());
    }
}
