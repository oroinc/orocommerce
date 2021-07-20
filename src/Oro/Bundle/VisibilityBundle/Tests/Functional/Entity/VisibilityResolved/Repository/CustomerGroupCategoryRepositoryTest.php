<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupCategoryRepository;

class CustomerGroupCategoryRepositoryTest extends AbstractCategoryRepositoryTest
{
    use CatalogTrait;

    /**
     * @var CustomerGroupCategoryRepository
     */
    protected $repository;

    /**
     * @dataProvider getVisibilitiesForCustomerGroupsDataProvider
     * @param string $categoryName
     * @param array $customerGroups
     * @param array $visibilities
     */
    public function testGetVisibilitiesForCustomerGroups(
        $categoryName,
        $customerGroups,
        $visibilities
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $customerGroups = array_map(
            function ($customerGroupName) {
                return $this->getReference($customerGroupName);
            },
            $customerGroups
        );

        $actualVisibility = $this->getRepository()
            ->getVisibilitiesForCustomerGroups($this->scopeManager, $category, $customerGroups);

        $expectedVisibilities = [];
        foreach ($visibilities as $customer => $expectedVisibility) {
            /** @var CustomerGroup $customer */
            $customerGroup = $this->getReference($customer);
            $expectedVisibilities[$customerGroup->getId()] = $expectedVisibility;
        }

        $this->assertEquals($expectedVisibilities, $actualVisibility);
    }

    /**
     * @return array
     */
    public function getVisibilitiesForCustomerGroupsDataProvider()
    {
        return [
            [
                'categoryName' => 'category_1',
                'customers' => [
                    'customer_group.group1',
                    'customer_group.group3',
                ],
                'visibilities' => [
                    'customer_group.group1' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'customer_group.group3' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                ],
            ],
            [
                'categoryName' => 'category_1_2',
                'customers' => [
                    'customer_group.group1',
                    'customer_group.group3',
                ],
                'visibilities' => [
                    'customer_group.group1' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'customer_group.group3' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
            ],
            [
                'categoryName' => 'category_1_2_3',
                'customers' => [
                    'customer_group.group1',
                    'customer_group.group3',
                ],
                'visibilities' => [
                    'customer_group.group1' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'customer_group.group3' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                ],
            ],
        ];
    }

    /**
     * @dataProvider isCategoryVisibleDataProvider
     * @param string $categoryName
     * @param string $customerGroupName
     * @param int $configValue
     * @param bool $expectedVisibility
     */
    public function testIsCategoryVisible($categoryName, $customerGroupName, $configValue, $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference($customerGroupName);
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
        $actualVisibility = $this->repository->isCategoryVisible($category, $configValue, $scope);

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
                'customerGroupName' => 'customer_group.group1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1',
                'customerGroupName' => 'customer_group.group3',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'customerGroupName' => 'customer_group.group1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2',
                'customerGroupName' => 'customer_group.group3',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'customerGroupName' => 'customer_group.group1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'customerGroupName' => 'customer_group.group3',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
        ];
    }

    /**
     * @dataProvider getCategoryIdsByVisibilityDataProvider
     * @param int $visibility
     * @param string $customerGroupName
     * @param int $configValue
     * @param array $expected
     */
    public function testGetCategoryIdsByVisibility($visibility, $customerGroupName, $configValue, array $expected)
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference($customerGroupName);
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
        $categoryIds = $this->repository->getCategoryIdsByVisibility($visibility, $scope, $configValue);

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
                'customerGroupName' => 'customer_group.group1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_5',
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'customerGroupName' => 'customer_group.group1',
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
                'customerGroupName' => 'customer_group.group2',
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
                'customerGroupName' => 'customer_group.group2',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'customerGroupName' => 'customer_group.group3',
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
                'customerGroupName' => 'customer_group.group3',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_2_3',
                    'category_1_2_3_4',
                ]
            ],
        ];
    }

    /**
     * @dataProvider getParentCategoryVisibilitiesDataProvider
     */
    public function testGetParentCategoryVisibilities(array $expectedVisibilities)
    {
        $expectedVisibilities = $this->convertReferences($expectedVisibilities);
        $actualVisibilities = $this->repository->getParentCategoryVisibilities();

        $this->assertSameSize($expectedVisibilities, $actualVisibilities);
        foreach ($actualVisibilities as $actualVisibility) {
            static::assertContainsEquals(
                $actualVisibility,
                $expectedVisibilities,
                \var_export($expectedVisibilities, true)
            );
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getParentCategoryVisibilitiesDataProvider()
    {
        return [
            'all parent category visibilities' => [[
                [
                    'visibility_id' => 'category_1.visibility.customer_group.group3',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1',
                    'parent_category_id' => self::ROOT_CATEGORY,
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                ],
                [
                    'visibility_id' => 'category_1_2.visibility.customer_group.group1',
                    'parent_visibility_id' => 'category_1.visibility.customer_group.group1',
                    'parent_visibility' => CustomerGroupCategoryVisibility::HIDDEN,
                    'category_id' => 'category_1_2',
                    'parent_category_id' => 'category_1',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2.visibility.customer_group.group2',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1_2',
                    'parent_category_id' => 'category_1',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5.visibility.customer_group.group3',
                    'parent_visibility_id' => 'category_1.visibility.customer_group.group3',
                    'parent_visibility' => CustomerGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5',
                    'parent_category_id' => 'category_1',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2_3.visibility.customer_group.group1',
                    'parent_visibility_id' => 'category_1_2.visibility.customer_group.group1',
                    'parent_visibility' => CustomerGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_2_3',
                    'parent_category_id' => 'category_1_2',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5_6.visibility.customer_group.group1',
                    'parent_visibility_id' => null,
                    'parent_visibility' => null,
                    'category_id' => 'category_1_5_6',
                    'parent_category_id' => 'category_1_5',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5_6.visibility.customer_group.group3',
                    'parent_visibility_id' => 'category_1_5.visibility.customer_group.group3',
                    'parent_visibility' => CustomerGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6',
                    'parent_category_id' => 'category_1_5',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2_3_4.visibility.customer_group.group3',
                    'parent_visibility_id' => 'category_1_2_3.visibility.customer_group.group3',
                    'parent_visibility' => CustomerGroupCategoryVisibility::HIDDEN,
                    'category_id' => 'category_1_2_3_4',
                    'parent_category_id' => 'category_1_2_3',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2_3_4.visibility.customer_group.group1',
                    'parent_visibility_id' => 'category_1_2_3.visibility.customer_group.group1',
                    'parent_visibility' => CustomerGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_2_3_4',
                    'parent_category_id' => 'category_1_2_3',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_2_3_4.visibility.customer_group.anonymous',
                    'parent_visibility_id' => 'category_1_2_3.visibility.customer_group.anonymous',
                    'parent_visibility' => CustomerGroupCategoryVisibility::HIDDEN,
                    'category_id' => 'category_1_2_3_4',
                    'parent_category_id' => 'category_1_2_3',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
                [
                    'visibility_id' => 'category_1_5_6_7.visibility.customer_group.group1',
                    'parent_visibility_id' => 'category_1_5_6.visibility.customer_group.group1',
                    'parent_visibility' => CustomerGroupCategoryVisibility::PARENT_CATEGORY,
                    'category_id' => 'category_1_5_6_7',
                    'parent_category_id' => 'category_1_5_6',
                    'parent_category_resolved_visibility' => CategoryVisibilityResolved::VISIBILITY_HIDDEN,
                ],
                [
                    'visibility_id' => 'category_1_5_6_7.visibility.customer_group.group3',
                    'parent_visibility_id' => 'category_1_5_6.visibility.customer_group.group3',
                    'parent_visibility' => CustomerGroupCategoryVisibility::PARENT_CATEGORY,
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
        /** @var CustomerGroupCategoryVisibility $visibility */
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
            return $this->getRootCategory()->getId();
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
        /** @var CustomerGroupCategoryVisibility[] $visibilities */
        $visibilities = $this->getManagerRegistry()
            ->getManagerForClass('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility')
            ->createQueryBuilder('entity')
            ->andWhere('entity.visibility IN (:scalarVisibilities)')
            ->setParameter(
                'scalarVisibilities',
                [CustomerGroupCategoryVisibility::VISIBLE, CustomerGroupCategoryVisibility::HIDDEN]
            )
            ->getQuery()
            ->getResult();
        $this->assertNotEmpty($visibilities);

        /** @var CustomerGroupCategoryVisibility[] $indexedVisibilities */
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
            $customerGroup = $visibility->getScope()->getCustomerGroup();
            $this->assertEquals($customerGroup->getId(), $resolvedVisibility['customerGroup']);
            $this->assertEquals(CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC, $resolvedVisibility['source']);
            if ($visibility->getVisibility() === CustomerGroupCategoryVisibility::VISIBLE) {
                $this->assertEquals(
                    CustomerGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                    $resolvedVisibility['visibility']
                );
            } else {
                $this->assertEquals(
                    CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    $resolvedVisibility['visibility']
                );
            }
        }
    }

    public function testInsertParentCategoryValues()
    {
        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference('customer_group.group3');

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
        $resolvedVisibilities = $this->filterVisibilitiesByCustomerGroup(
            $resolvedVisibilities,
            $customerGroup->getId()
        );

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
     * @param $customerGroupId
     * @return array
     */
    protected function filterVisibilitiesByCustomerGroup(array $visibilities, $customerGroupId)
    {
        $currentCustomerGroupVisibilities = [];
        foreach ($visibilities as $visibility) {
            if ($visibility['customerGroup'] == $customerGroupId) {
                $currentCustomerGroupVisibilities[] = $visibility;
            }
        }

        return $currentCustomerGroupVisibilities;
    }

    /**
     * @return CustomerGroupCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_visibility.customer_group_category_repository');
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
                'IDENTITY(scope.customerGroup) as customerGroup',
                'entity.visibility',
                'entity.source'
            )
            ->join('entity.scope', 'scope')
            ->getQuery()
            ->getArrayResult();
    }
}
