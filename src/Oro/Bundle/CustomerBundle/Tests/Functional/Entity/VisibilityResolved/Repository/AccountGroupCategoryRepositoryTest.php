<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\AccountGroupCategoryVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository;
use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class AccountGroupCategoryRepositoryTest extends AbstractCategoryRepositoryTest
{
    /**
     * @var AccountGroupCategoryRepository
     */
    protected $repository;

    /**
     * @dataProvider getVisibilitiesForAccountGroupsDataProvider
     * @param string $categoryName
     * @param array $accountGroups
     * @param array $visibilities
     */
    public function testGetVisibilitiesForAccountGroups(
        $categoryName,
        $accountGroups,
        $visibilities
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $accountGroups = array_map(
            function ($accountGroupName) {
                return $this->getReference($accountGroupName);
            },
            $accountGroups
        );

        $actualVisibility = $this->getRepository()
            ->getVisibilitiesForAccountGroups($category, $accountGroups);

        $expectedVisibilities = [];
        foreach ($visibilities as $account => $expectedVisibility) {
            /** @var AccountGroup $account */
            $accountGroup = $this->getReference($account);
            $expectedVisibilities[$accountGroup->getId()] = $expectedVisibility;
        }

        $this->assertEquals($expectedVisibilities, $actualVisibility);
    }

    /**
     * @return array
     */
    public function getVisibilitiesForAccountGroupsDataProvider()
    {
        return [
            [
                'categoryName' => 'category_1',
                'accounts' => [
                    'account_group.group1',
                    'account_group.group3',
                ],
                'visibilities' => [
                    'account_group.group1' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'account_group.group3' => AccountGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                ],
            ],
            [
                'categoryName' => 'category_1_2',
                'accounts' => [
                    'account_group.group1',
                    'account_group.group3',
                ],
                'visibilities' => [
                    'account_group.group1' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'account_group.group3' => AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accounts' => [
                    'account_group.group1',
                    'account_group.group3',
                ],
                'visibilities' => [
                    'account_group.group1' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'account_group.group3' => AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                ],
            ],
        ];
    }

    /**
     * @dataProvider isCategoryVisibleDataProvider
     * @param string $categoryName
     * @param string $accountGroupName
     * @param int $configValue
     * @param bool $expectedVisibility
     */
    public function testIsCategoryVisible($categoryName, $accountGroupName, $configValue, $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroupName);

        $actualVisibility = $this->repository->isCategoryVisible($category, $accountGroup, $configValue);

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
                'accountGroupName' => 'account_group.group1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1',
                'accountGroupName' => 'account_group.group3',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'accountGroupName' => 'account_group.group1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2',
                'accountGroupName' => 'account_group.group3',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountGroupName' => 'account_group.group1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountGroupName' => 'account_group.group3',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
        ];
    }

    /**
     * @dataProvider getCategoryIdsByVisibilityDataProvider
     * @param int $visibility
     * @param string $accountGroupName
     * @param int $configValue
     * @param array $expected
     */
    public function testGetCategoryIdsByVisibility($visibility, $accountGroupName, $configValue, array $expected)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroupName);

        $categoryIds = $this->repository->getCategoryIdsByVisibility($visibility, $accountGroup, $configValue);

        $expectedCategoryIds = [];
        foreach ($expected as $categoryName) {
            /** @var Category $category */
            $category = $this->getReference($categoryName);
            $expectedCategoryIds[] = $category->getId();
        }

        if ($visibility == BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE) {
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
                'accountGroupName' => 'account_group.group1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_5',
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountGroupName' => 'account_group.group1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'accountGroupName' => 'account_group.group2',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5',
                    'category_1_5_6',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountGroupName' => 'account_group.group2',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'accountGroupName' => 'account_group.group3',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1',
                    'category_1_2',
                    'category_1_5',
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountGroupName' => 'account_group.group3',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_2_3',
                    'category_1_2_3_4',
                ]
            ],
        ];
    }

    /**
     * @param array $expectedVisibilities
     * @dataProvider getParentCategoryVisibilitiesDataProvider
     */
    public function testGetParentCategoryVisibilities(array $expectedVisibilities)
    {
        $expectedVisibilities = $this->convertReferences($expectedVisibilities);
        $actualVisibilities = $this->repository->getParentCategoryVisibilities();

        $this->assertSameSize($expectedVisibilities, $actualVisibilities);
        foreach ($actualVisibilities as $actualVisibility) {
            $this->assertContains($actualVisibility, $expectedVisibilities);
        }
    }

    /**
     * @return array
     */
    public function getParentCategoryVisibilitiesDataProvider()
    {
        return [
            'all parent category visibilities' => [[
                [
                    'visibility_id' => 'category_1.visibility.account_group.group3',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1',
                    'parent_category_id' => self::ROOT_CATEGORY,
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                ],
                [
                    'visibility_id' => 'category_1_2.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::HIDDEN,
                    'category_id' => 'category_1_2',
                    'parent_category_id' => 'category_1',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2.visibility.account_group.group2',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1_2',
                    'parent_category_id' => 'category_1',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5',
                    'parent_category_id' => 'category_1',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2_3.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1_2.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_2_3',
                    'parent_category_id' => 'category_1_2',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5_6.visibility.account_group.group1',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1_5_6',
                    'parent_category_id' => 'category_1_5',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5_6.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1_5.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6',
                    'parent_category_id' => 'category_1_5',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2_3_4.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1_2_3.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::HIDDEN,
                    'category_id' => 'category_1_2_3_4',
                    'parent_category_id' => 'category_1_2_3',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2_3_4.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1_2_3.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_2_3_4',
                    'parent_category_id' => 'category_1_2_3',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5_6_7.visibility.account_group.group1',
                    'parent_visibility_id' => 'category_1_5_6.visibility.account_group.group1',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6_7',
                    'parent_category_id' => 'category_1_5_6',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_HIDDEN,
                ],
                [
                    'visibility_id' => 'category_1_5_6_7.visibility.account_group.group3',
                    'parent_visibility_id' => 'category_1_5_6.visibility.account_group.group3',
                    'parent_visibility' => AccountGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6_7',
                    'parent_category_id' => 'category_1_5_6',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_HIDDEN,
                ],
            ]]
        ];
    }

    /**
     * @param array $data
     * @return array
     */
    protected function convertReferences(array $data)
    {
        foreach ($data as $key => $row) {
            if (is_string($row['visibility_id'])) {
                $data[$key]['visibility_id'] = $this->getVisibilityId($row['visibility_id']);
            }
            if (is_string($row['parent_visibility_id'])) {
                $data[$key]['parent_visibility_id'] = $this->getVisibilityId($row['parent_visibility_id']);
            }
            if (is_string($row['category_id'])) {
                $data[$key]['category_id'] = $this->getCategoryId($row['category_id']);
            }
            if (is_string($row['parent_category_id'])) {
                $data[$key]['parent_category_id'] = $this->getCategoryId($row['parent_category_id']);
            }
        }
        return $data;
    }

    /**
     * @param string $reference
     * @return int
     */
    protected function getVisibilityId($reference)
    {
        /** @var AccountGroupCategoryVisibility $visibility */
        $visibility = $this->getReference($reference);

        return $visibility->getId();
    }

    /**
     * @param string $reference
     * @return integer
     */
    protected function getCategoryId($reference)
    {
        if ($reference === self::ROOT_CATEGORY) {
            return $this->getContainer()->get('doctrine')
                ->getRepository('OroCatalogBundle:Category')
                ->getMasterCatalogRoot()
                ->getId();
        }

        return $reference ? $this->getReference($reference)->getId() : null;
    }

    public function testClearTable()
    {
        $this->assertGreaterThan(0, $this->getEntitiesCount());
        $this->repository->clearTable();
        $this->assertEquals(0, $this->getEntitiesCount());
    }

    public function testInsertStaticValues()
    {
        /** @var AccountGroupCategoryVisibility[] $visibilities */
        $visibilities = $this->getManagerRegistry()
            ->getManagerForClass('OroCustomerBundle:Visibility\AccountGroupCategoryVisibility')
            ->getRepository('OroCustomerBundle:Visibility\AccountGroupCategoryVisibility')
            ->createQueryBuilder('entity')
            ->andWhere('entity.visibility IN (:scalarVisibilities)')
            ->setParameter(
                'scalarVisibilities',
                [AccountGroupCategoryVisibility::VISIBLE, AccountGroupCategoryVisibility::HIDDEN]
            )
            ->getQuery()
            ->getResult();
        $this->assertNotEmpty($visibilities);

        /** @var AccountGroupCategoryVisibility[] $indexedVisibilities */
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
            $this->assertEquals($visibility->getAccountGroup()->getId(), $resolvedVisibility['accountGroup']);
            $this->assertEquals(AccountGroupCategoryVisibilityResolved::SOURCE_STATIC, $resolvedVisibility['source']);
            if ($visibility->getVisibility() === AccountGroupCategoryVisibility::VISIBLE) {
                $this->assertEquals(
                    AccountGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                    $resolvedVisibility['visibility']
                );
            } else {
                $this->assertEquals(
                    AccountGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    $resolvedVisibility['visibility']
                );
            }
        }
    }

    public function testInsertParentCategoryValues()
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group3');

        $parentCategoryFallbackCategories = ['category_1_5','category_1_5_6', 'category_1_5_6_7'];
        $parentCategoryFallbackCategoryIds = [];
        foreach ($parentCategoryFallbackCategories as $categoryReference) {
            /** @var Category $category */
            $category = $this->getReference($categoryReference);
            $parentCategoryFallbackCategoryIds[] = $category->getId();
        }

        $parentCategoryVisibilities = $this->getCategoryVisibilities($parentCategoryFallbackCategoryIds);

        /** @var Category $staticCategory */
        $staticCategory = $this->getReference('category_1_2_3');
        $staticCategoryId = $staticCategory->getId();

        $staticCategoryVisibilities = $this->getCategoryVisibilities([$staticCategoryId]);

        $visibility = CategoryVisibilityResolved::VISIBILITY_VISIBLE;
        $this->repository->clearTable();
        $this->repository->insertParentCategoryValues(
            $this->getInsertExecutor(),
            array_merge($parentCategoryVisibilities, $staticCategoryVisibilities),
            $visibility
        );

        $resolvedVisibilities = $this->getResolvedVisibilities();
        $resolvedVisibilities = $this->filterVisibilitiesByAccountGroup($resolvedVisibilities, $accountGroup->getId());

        // static visibilities should not be inserted
        $this->assertSameSize($parentCategoryFallbackCategoryIds, $resolvedVisibilities);
        foreach ($resolvedVisibilities as $resolvedVisibility) {
            $this->assertContains($resolvedVisibility['category'], $parentCategoryFallbackCategoryIds);
            $this->assertEquals(CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY, $resolvedVisibility['source']);
            $this->assertEquals($visibility, $resolvedVisibility['visibility']);
        }
    }

    /**
     * @param array $categoryIds
     * @return array
     */
    protected function getCategoryVisibilities(array $categoryIds)
    {
        $groupVisibilities = $this->repository->getParentCategoryVisibilities();

        $visibilities = [];
        foreach ($groupVisibilities as $groupVisibility) {
            if (in_array($groupVisibility['category_id'], $categoryIds)) {
                $visibilities[] = $groupVisibility['visibility_id'];
            }
        }

        return $visibilities;
    }

    /**
     * @param array $visibilities
     * @param $accountGroupId
     * @return array
     */
    protected function filterVisibilitiesByAccountGroup(array $visibilities, $accountGroupId)
    {
        $currentAccountGroupVisibilities = [];
        foreach ($visibilities as $visibility) {
            if ($visibility['accountGroup'] == $accountGroupId) {
                $currentAccountGroupVisibilities[] = $visibility;
            }
        }

        return $currentAccountGroupVisibilities;
    }

    /**
     * @return AccountGroupCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroCustomerBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
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
                'IDENTITY(entity.accountGroup) as accountGroup',
                'entity.visibility',
                'entity.source'
            )
            ->getQuery()
            ->getArrayResult();
    }
}
