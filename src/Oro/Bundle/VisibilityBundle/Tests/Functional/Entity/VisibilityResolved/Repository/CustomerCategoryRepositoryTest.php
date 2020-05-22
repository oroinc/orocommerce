<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerCategoryRepository;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CustomerCategoryRepositoryTest extends AbstractCategoryRepositoryTest
{
    /** @var CustomerCategoryRepository */
    protected $repository;

    /**
     * @dataProvider getVisibilitiesForCustomersDataProvider
     * @param string $categoryName
     * @param array $customers
     * @param array $visibilities
     */
    public function testGetVisibilitiesForCustomers(
        $categoryName,
        array $customers,
        array $visibilities
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $customers = array_map(
            function ($customerName) {
                return $this->getReference($customerName);
            },
            $customers
        );

        $actualVisibility = $this->getRepository()
            ->getVisibilitiesForCustomers($this->scopeManager, $category, $customers);

        $expectedVisibilities = [];
        foreach ($visibilities as $customer => $expectedVisibility) {
            /** @var Customer $customer */
            $customer = $this->getReference($customer);
            $expectedVisibilities[$customer->getId()] = $expectedVisibility;
        }

        $this->assertEquals($expectedVisibilities, $actualVisibility);
    }

    /**
     * @return array
     */
    public function getVisibilitiesForCustomersDataProvider()
    {
        return [
            [
                'categoryName' => 'category_1',
                'customers' => [
                    'customer.level_1',
                    'customer.level_1.1',
                    'customer.level_1.2',
                ],
                'visibilities' => [
                    'customer.level_1' => CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                    'customer.level_1.1' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'customer.level_1.2' => CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                ],
            ],
            [
                'categoryName' => 'category_1_2',
                'customers' => [
                    'customer.level_1',
                    'customer.level_1.1',
                ],
                'visibilities' => [
                    'customer.level_1' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'customer.level_1.1' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                ],
            ],
            [
                'categoryName' => 'category_1_2_3',
                'customers' => [
                    'customer.level_1',
                    'customer.level_1.1',
                    'customer.level_1.2',
                ],
                'visibilities' => [
                    'customer.level_1' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'customer.level_1.1' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'customer.level_1.2' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
            ]
        ];
    }

    /**
     * @dataProvider isCategoryVisibleDataProvider
     * @param string $categoryName
     * @param string $customerName
     * @param int $configValue
     * @param bool $expectedVisibility
     */
    public function testIsCategoryVisible($categoryName, $customerName, $configValue, $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        /** @var Customer $customer */
        $customer = $this->getReference($customerName);
        $scope = $this->scopeManager->findOrCreate('customer_category_visibility', ['customer' => $customer]);
        $groupScope = $this->scopeManager->findOrCreate(
            'customer_group_category_visibility',
            ['customerGroup' => $customer->getGroup()]
        );
        $actualVisibility = $this->repository->isCategoryVisible($category, $configValue, $scope, $groupScope);

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
                'customerName' => 'customer.level_1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1',
                'customerName' => 'customer.level_1.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1',
                'customerName' => 'customer.level_1.2',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'customerName' => 'customer.level_1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2',
                'customerName' => 'customer.level_1.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'customerName' => 'customer.level_1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'customerName' => 'customer.level_1.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'customerName' => 'customer.level_1.2',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => true,
            ]
        ];
    }

//    /**
//     * @dataProvider getCategoryIdsByVisibilityDataProvider
//     * @param int $visibility
//     * @param string $customerName
//     * @param int $configValue
//     * @param array $expected
//     */
//    public function testGetCategoryIdsByVisibility($visibility, $customerName, $configValue, array $expected)
//    {
//        /** @var Customer $customer */
//        $customer = $this->getReference($customerName);
//
//        $categoryIds = $this->repository->getCategoryIdsByVisibility($visibility, $customer, $configValue);
//
//        $expectedCategoryIds = [];
//        foreach ($expected as $categoryName) {
//            /** @var Category $category */
//            $category = $this->getReference($categoryName);
//            $expectedCategoryIds[] = $category->getId();
//        }
//
//        if ($visibility == BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE) {
//            $masterCatalogId = $this->getMasterCatalog()->getId();
//            array_unshift($expectedCategoryIds, $masterCatalogId);
//        }
//
//        $this->assertEquals($expectedCategoryIds, $categoryIds);
//    }

    /**
     * @return array
     */
    public function getCategoryIdsByVisibilityDataProvider()
    {
        return [
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'customerName' => 'customer.level_1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1',
                    'category_1_5',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'customerName' => 'customer.level_1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'customerName' => 'customer.level_1.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_5',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'customerName' => 'customer.level_1.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'customerName' => 'customer.level_1.2',
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
                'customerName' => 'customer.level_1.2',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'customerName' => 'customer.level_1.2.1',
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
                'customerName' => 'customer.level_1.2.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_5_6_7'
                ]
            ],
        ];
    }

//    /**
//     * @dataProvider updateCustomerCategoryVisibilityByCategoryDataProvider
//     * @param string $customer
//     * @param array $categories
//     * @param int $visibility
//     */
//    public function testUpdateCustomerCategoryVisibilityByCategory($customer, array $categories, $visibility)
//    {
//        /** @var Customer $customer */
//        $customer = $this->getReference($customer);
//
//        /** @var Category[] $categoriesForUpdate */
//        $categoriesForUpdate = [];
//        foreach ($categories as $categoryName) {
//            $categoriesForUpdate[] = $this->getReference($categoryName);
//        }
//
//        $categoryIdsForUpdate = array_filter(
//            $categoriesForUpdate,
//            function (Category $category) {
//                return $category->getId();
//            }
//        );
//
//        $this->repository->updateCustomerCategoryVisibilityByCategory(
//            $customer,
//            $categoryIdsForUpdate,
//            $visibility
//        );
//
//        foreach ($categoriesForUpdate as $category) {
//            $visibilityResolved = $this->repository->findByPrimaryKey($category, $customer);
//            $this->assertEquals($visibility, $visibilityResolved->getVisibility());
//        }
//    }

    /**
     * @return array
     */
    public function updateCustomerCategoryVisibilityByCategoryDataProvider()
    {
        return [
            'Change visibility to visible' => [
                'customer' => 'customer.level_1',
                'categories' => [
                    'category_1',
                    'category_1_5_6',
                    'category_1_5_6_7'
                ],
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            ],
            'Change visibility to hidden' => [
                'customer' => 'customer.level_1.1',
                'categories' => [
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4'
                ],
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN
            ]
        ];
    }

    public function testFindByPrimaryKey()
    {
        /** @var CustomerCategoryVisibilityResolved $actualEntity */
        $actualEntity = $this->repository->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $expectedEntity = $this->repository->findByPrimaryKey(
            $actualEntity->getCategory(),
            $actualEntity->getScope()
        );

        $this->assertEquals(spl_object_hash($expectedEntity), spl_object_hash($actualEntity));
    }

    public function testInsertStaticValues()
    {
        /** @var CustomerCategoryVisibility[] $visibilities */
        $visibilities = $this->getManagerRegistry()
            ->getManagerForClass('OroVisibilityBundle:Visibility\CustomerCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\CustomerCategoryVisibility')
            ->createQueryBuilder('entity')
            ->andWhere('entity.visibility IN (:scalarVisibilities)')
            ->setParameter(
                'scalarVisibilities',
                [CustomerCategoryVisibility::VISIBLE, CustomerCategoryVisibility::HIDDEN]
            )
            ->getQuery()
            ->getResult();
        $this->assertNotEmpty($visibilities);

        /** @var CustomerCategoryVisibility[] $indexedVisibilities */
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
            $this->assertEquals($visibility->getScope()->getId(), $resolvedVisibility['scope']);
            $this->assertEquals(CustomerCategoryVisibilityResolved::SOURCE_STATIC, $resolvedVisibility['source']);
            if ($visibility->getVisibility() === CustomerCategoryVisibility::VISIBLE) {
                $this->assertEquals(
                    CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                    $resolvedVisibility['visibility']
                );
            } else {
                $this->assertEquals(
                    CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    $resolvedVisibility['visibility']
                );
            }
        }
    }

    public function testInsertCategoryValues()
    {
        /** @var CustomerCategoryVisibility[] $visibilities */
        $visibilities = $this->getManagerRegistry()
            ->getManagerForClass('OroVisibilityBundle:Visibility\CustomerCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\CustomerCategoryVisibility')
            ->createQueryBuilder('entity')
            ->andWhere('entity.visibility = :categoryVisibility')
            ->setParameter('categoryVisibility', CustomerCategoryVisibility::CATEGORY)
            ->getQuery()
            ->getResult();
        $this->assertNotEmpty($visibilities);

        /** @var CustomerCategoryVisibility[] $indexedVisibilities */
        $indexedVisibilities = [];
        foreach ($visibilities as $visibility) {
            $indexedVisibilities[$visibility->getId()] = $visibility;
        }

        $this->repository->clearTable();
        $insertExecutor = $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor');
        $this->repository->insertCategoryValues($insertExecutor);

        $resolvedVisibilities = $this->getResolvedVisibilities();

        $this->assertSameSize($indexedVisibilities, $resolvedVisibilities);
        foreach ($resolvedVisibilities as $resolvedVisibility) {
            $id = $resolvedVisibility['sourceCategoryVisibility'];
            $this->assertArrayHasKey($id, $indexedVisibilities);
            $visibility = $indexedVisibilities[$id];

            $this->assertEquals($visibility->getCategory()->getId(), $resolvedVisibility['category']);
            $this->assertEquals($visibility->getScope()->getId(), $resolvedVisibility['scope']);
            $this->assertEquals(CustomerCategoryVisibilityResolved::SOURCE_STATIC, $resolvedVisibility['source']);
            $this->assertEquals($visibility->getVisibility(), CustomerCategoryVisibility::CATEGORY);
        }
    }

    public function testInsertParentCategoryValues()
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer.level_1.1');
        $scope = $this->scopeManager->find('customer_category_visibility', ['customer' => $customer]);
        $parentCategoryFallbackCategories = ['category_1_2','category_1_2_3'];
        $parentCategoryFallbackCategoryIds = [];
        foreach ($parentCategoryFallbackCategories as $categoryReference) {
            /** @var Category $category */
            $category = $this->getReference($categoryReference);
            $parentCategoryFallbackCategoryIds[] = $category->getId();
        }

        $parentCategoryVisibilities = $this->getCategoryVisibilities($parentCategoryFallbackCategoryIds);

        /** @var Category $staticCategory */
        $staticCategory = $this->getReference('category_1');
        $staticCategoryId = $staticCategory->getId();

        $staticCategoryVisibilities = $this->getCategoryVisibilities([$staticCategoryId]);

        $visibility = CategoryVisibilityResolved::VISIBILITY_VISIBLE;
        $this->repository->clearTable();
        $this->repository->insertParentCategoryValues(
            $this->getContainer()->get('oro_entity.orm.insert_from_select_query_executor'),
            array_merge($parentCategoryVisibilities, $staticCategoryVisibilities),
            $visibility
        );

        $resolvedVisibilities = $this->getResolvedVisibilities();
        $resolvedVisibilities = $this->filterVisibilitiesByCustomer($resolvedVisibilities, $scope->getId());

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
     * @param array $visibilities
     * @param $scopeId
     * @return array
     */
    protected function filterVisibilitiesByCustomer(array $visibilities, $scopeId)
    {
        $currentCustomerVisibilities = [];
        foreach ($visibilities as $visibility) {
            if ($visibility['scope'] == $scopeId) {
                $currentCustomerVisibilities[] = $visibility;
            }
        }

        return $currentCustomerVisibilities;
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
     * @return CustomerCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('oro_visibility.customer_category_repository');
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
                'IDENTITY(entity.scope) as scope',
                'entity.visibility',
                'entity.source'
            )
            ->getQuery()
            ->getArrayResult();
    }
}
