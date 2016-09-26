<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class CategoryRepositoryTest extends AbstractCategoryRepositoryTest
{
    /**
     * @var CategoryRepository
     */
    protected $repository;

    /**
     * @dataProvider isCategoryVisibleDataProvider
     * @param string $categoryName
     * @param int $configValue
     * @param bool $expectedVisibility
     */
    public function testIsCategoryVisible($categoryName, $configValue, $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $actualVisibility = $this->getRepository()->isCategoryVisible($category, $configValue);

        $this->assertEquals($expectedVisibility, $actualVisibility);
    }

    /**
     * @return array
     */
    public function isCategoryVisibleDataProvider()
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
     * @param int $visibility
     * @param int $configValue
     * @param array $expected
     */
    public function testGetCategoryIdsByVisibility($visibility, $configValue, array $expected)
    {
        $categoryIds = $this->repository->getCategoryIdsByVisibility($visibility, $configValue);

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

    /**
     * @return array
     */
    public function getCategoryIdsByVisibilityDataProvider()
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
        $this->repository->clearTable();
        $this->assertEquals(0, $this->getEntitiesCount());
    }

    public function testInsertStaticValues()
    {
        /** @var CategoryVisibility[] $visibilities */
        $visibilities = $this->getManagerRegistry()
            ->getManagerForClass('OroVisibilityBundle:Visibility\CategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\CategoryVisibility')
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

        $this->repository->clearTable();
        $this->repository->insertStaticValues($this->getInsertExecutor());

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

        $this->repository->clearTable();
        $this->repository->insertParentCategoryValues(
            $this->getInsertExecutor(),
            array_merge($parentCategoryFallbackCategoryIds, [$staticCategoryId]),
            $visibility
        );

        $resolvedVisibilities = $this->getResolvedVisibilities();
        // static visibilities should not be inserted
        $this->assertSameSize($parentCategoryFallbackCategoryIds, $resolvedVisibilities);

        foreach ($resolvedVisibilities as $resolvedVisibility) {
            $this->assertContains($resolvedVisibility['category'], $parentCategoryFallbackCategoryIds);
            $this->assertEquals(CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY, $resolvedVisibility['source']);
            $this->assertEquals($visibility, $resolvedVisibility['visibility']);
        }
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortByCategoryId(array $a, array $b)
    {
        return $a['category_id'] > $b['category_id'] ? 1 : -1;
    }

    /**
     * @param array $categories
     * @return array
     */
    protected function replaceReferencesWithIds(array $categories)
    {
        $rootCategory = $this->getMasterCatalog();

        foreach ($categories as $key => $row) {
            $category = $row['category_id'];
            /** @var Category $category */
            if ($category === self::ROOT_CATEGORY) {
                $category = $rootCategory;
            } else {
                $category = $this->getReference($category);
            }
            $categories[$key]['category_id'] = $category->getId();

            $parentCategory = $row['parent_category_id'];
            if ($parentCategory) {
                /** @var Category $parentCategory */
                if ($parentCategory === self::ROOT_CATEGORY) {
                    $parentCategory = $rootCategory;
                } else {
                    $parentCategory = $this->getReference($parentCategory);
                }
                $categories[$key]['parent_category_id'] = $parentCategory->getId();
            }
        }

        return $categories;
    }

    /**
     * @return CategoryRepository
     */
    protected function getRepository()
    {
        return $this->getManagerRegistry()
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved');
    }

    /**
     * @return array
     */
    protected function getResolvedVisibilities()
    {
        return $this->repository->createQueryBuilder('entity')
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
