<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;

class CategoryRepositoryTest extends AbstractCategoryRepositoryTest
{
    /**
     * @dataProvider isCategoryVisibleDataProvider
     */
    public function testIsCategoryVisible(string $categoryName, int $configValue, bool $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $actualVisibility = $this->getRepository()->isCategoryVisible($category, $configValue);

        $this->assertSame($expectedVisibility, $actualVisibility);
    }

    public function isCategoryVisibleDataProvider(): array
    {
        return [
            [
                'categoryName' => 'category_1',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'configValue' => 1,
                'expectedVisibility' => true,
            ],
        ];
    }

    /**
     * @dataProvider getCategoryIdsByVisibilityDataProvider
     */
    public function testGetCategoryIdsByVisibility(int $visibility, int $configValue, array $expected)
    {
        $categoryIds = $this->getRepository()->getCategoryIdsByVisibility($visibility, $configValue);

        $expectedCategoryIds = [];
        foreach ($expected as $categoryName) {
            /** @var Category $category */
            $category = $this->getReference($categoryName);
            $expectedCategoryIds[] = $category->getId();
        }

        if ($visibility === BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE) {
            $masterCatalogId = $this->getMasterCatalog()->getId();
            array_unshift($expectedCategoryIds, $masterCatalogId);
        }

        $this->assertEquals($expectedCategoryIds, $categoryIds);
    }

    public function getCategoryIdsByVisibilityDataProvider(): array
    {
        return [
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'configValue' => 1,
                'expected' => [
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'configValue' => 1,
                'expected' => [
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ],
        ];
    }

    public function testClearTable()
    {
        $this->assertGreaterThan(0, $this->getEntitiesCount());
        $this->getRepository()->clearTable();
        $this->assertEquals(0, $this->getEntitiesCount());
    }

    public function testInsertStaticValues()
    {
        /** @var CategoryVisibility[] $visibilities */
        $visibilities = $this->getDoctrine()
            ->getRepository(CategoryVisibility::class)
            ->createQueryBuilder('entity')
            ->andWhere('entity.visibility IN (:scalarVisibilities)')
            ->setParameter('scalarVisibilities', [CategoryVisibility::VISIBLE, CategoryVisibility::HIDDEN])
            ->getQuery()
            ->getResult();
        $this->assertNotEmpty($visibilities);

        /** @var CategoryVisibility[] $indexedVisibilities */
        $indexedVisibilities = [];
        foreach ($visibilities as $visibility) {
            $indexedVisibilities[$visibility->getId()] = $visibility;
        }

        $this->getRepository()->clearTable();
        $scope = $this->getScopeManager()->findOrCreate(CategoryVisibility::VISIBILITY_TYPE);
        $this->getRepository()->insertStaticValues($this->getInsertExecutor(), $scope);

        $resolvedVisibilities = $this->getResolvedVisibilities();

        $this->assertSameSize($indexedVisibilities, $resolvedVisibilities);
        foreach ($resolvedVisibilities as $resolvedVisibility) {
            $id = $resolvedVisibility['sourceCategoryVisibility'];
            $this->assertArrayHasKey($id, $indexedVisibilities);
            $visibility = $indexedVisibilities[$id];

            $this->assertEquals($visibility->getCategory()->getId(), $resolvedVisibility['category']);
            $this->assertEquals(CategoryVisibilityResolved::SOURCE_STATIC, $resolvedVisibility['source']);
            if ($visibility->getVisibility() === CategoryVisibility::VISIBLE) {
                $this->assertEquals(CategoryVisibilityResolved::VISIBILITY_VISIBLE, $resolvedVisibility['visibility']);
            } else {
                $this->assertEquals(CategoryVisibilityResolved::VISIBILITY_HIDDEN, $resolvedVisibility['visibility']);
            }
        }
    }

    public function testInsertParentCategoryValues()
    {
        $parentCategoryFallbackCategories = ['category_1_2', 'category_1_2_3_4'];
        $parentCategoryFallbackCategoryIds = [];
        foreach ($parentCategoryFallbackCategories as $categoryReference) {
            /** @var Category $category */
            $category = $this->getReference($categoryReference);
            $parentCategoryFallbackCategoryIds[] = $category->getId();
        }

        /** @var Category $staticCategory */
        $staticCategory = $this->getReference('category_1_2_3');
        $staticCategoryId = $staticCategory->getId();

        $visibility = CategoryVisibilityResolved::VISIBILITY_VISIBLE;
        $scope = $this->getScopeManager()->findOrCreate(CategoryVisibility::VISIBILITY_TYPE);
        $this->getRepository()->clearTable();
        $this->getRepository()->insertParentCategoryValues(
            $this->getInsertExecutor(),
            array_merge($parentCategoryFallbackCategoryIds, [$staticCategoryId]),
            $visibility,
            $scope
        );

        $resolvedVisibilities = $this->getResolvedVisibilities();
        // static visibilities should not be inserted
        $this->assertSameSize($parentCategoryFallbackCategoryIds, $resolvedVisibilities);

        foreach ($resolvedVisibilities as $resolvedVisibility) {
            static::assertContainsEquals(
                $resolvedVisibility['category'],
                $parentCategoryFallbackCategoryIds,
                \var_export($parentCategoryFallbackCategoryIds, true)
            );
            $this->assertEquals(CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY, $resolvedVisibility['source']);
            $this->assertEquals($visibility, $resolvedVisibility['visibility']);
        }
    }

    protected function getRepository(): CategoryRepository
    {
        return $this->getContainer()->get('oro_visibility.category_repository');
    }

    private function getResolvedVisibilities(): array
    {
        return $this->getRepository()->createQueryBuilder('entity')
            ->select(
                'IDENTITY(entity.sourceCategoryVisibility) as sourceCategoryVisibility',
                'IDENTITY(entity.category) as category',
                'entity.visibility',
                'entity.source'
            )
            ->getQuery()
            ->getArrayResult();
    }
}
