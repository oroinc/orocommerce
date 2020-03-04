<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ImportExport\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\ImportExport\Helper\CategoryImportExportHelper;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category as CategoryStub;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class CategoryImportExportHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var CategoryImportExportHelper */
    private $helper;

    protected function setUp()
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->helper = new CategoryImportExportHelper($this->doctrine);
    }

    /**
     * @dataProvider getCategoryPathDataProvider
     *
     * @param Category $category
     * @param string $expectedResult
     */
    public function testGetCategoryPath(Category $category, string $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->helper->getCategoryPath($category));
    }

    /**
     * @return array
     */
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

    /**
     * @param string $title
     *
     * @return CategoryStub
     */
    private function createCategory(string $title): CategoryStub
    {
        return (new CategoryStub())->addTitle((new CategoryTitle())->setString($title));
    }

    /**
     * @dataProvider getPersistedCategoryPathDataProvider
     *
     * @param array $categoryPath
     * @param string $expectedResult
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

    /**
     * @return array
     */
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
        $organization = $this->createMock(Organization::class);
        $repo = $this->mockGetRepository();

        $repo
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->with($organization)
            ->willReturn($rootCategory = new Category());

        $this->assertSame($rootCategory, $this->helper->getRootCategory($organization));
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
