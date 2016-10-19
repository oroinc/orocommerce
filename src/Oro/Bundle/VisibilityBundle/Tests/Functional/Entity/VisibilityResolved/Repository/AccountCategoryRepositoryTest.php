<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class AccountCategoryRepositoryTest extends AbstractCategoryRepositoryTest
{
    /** @var AccountCategoryRepository */
    protected $repository;

    /**
     * @dataProvider getVisibilitiesForAccountsDataProvider
     * @param string $categoryName
     * @param array $accounts
     * @param array $visibilities
     */
    public function testGetVisibilitiesForAccounts(
        $categoryName,
        array $accounts,
        array $visibilities
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        $accounts = array_map(
            function ($accountName) {
                return $this->getReference($accountName);
            },
            $accounts
        );

        $actualVisibility = $this->getRepository()
            ->getVisibilitiesForAccounts($category, $accounts);

        $expectedVisibilities = [];
        foreach ($visibilities as $account => $expectedVisibility) {
            /** @var Account $account */
            $account = $this->getReference($account);
            $expectedVisibilities[$account->getId()] = $expectedVisibility;
        }

        $this->assertEquals($expectedVisibilities, $actualVisibility);
    }

    /**
     * @return array
     */
    public function getVisibilitiesForAccountsDataProvider()
    {
        return [
            [
                'categoryName' => 'category_1',
                'accounts' => [
                    'account.level_1',
                    'account.level_1.1',
                    'account.level_1.2',
                ],
                'visibilities' => [
                    'account.level_1' => AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                    'account.level_1.1' => AccountCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'account.level_1.2' => AccountCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                ],
            ],
            [
                'categoryName' => 'category_1_2',
                'accounts' => [
                    'account.level_1',
                    'account.level_1.1',
                ],
                'visibilities' => [
                    'account.level_1' => AccountCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'account.level_1.1' => AccountCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                ],
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accounts' => [
                    'account.level_1',
                    'account.level_1.1',
                    'account.level_1.2',
                ],
                'visibilities' => [
                    'account.level_1' => AccountCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'account.level_1.1' => AccountCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    'account.level_1.2' => AccountCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                ],
            ]
        ];
    }

    /**
     * @dataProvider isCategoryVisibleDataProvider
     * @param string $categoryName
     * @param string $accountName
     * @param int $configValue
     * @param bool $expectedVisibility
     */
    public function testIsCategoryVisible($categoryName, $accountName, $configValue, $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        /** @var Account $account */
        $account = $this->getReference($accountName);

        $actualVisibility = $this->repository->isCategoryVisible($category, $configValue, $account);

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
                'accountName' => 'account.level_1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1',
                'accountName' => 'account.level_1.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1',
                'accountName' => 'account.level_1.2',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'accountName' => 'account.level_1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2',
                'accountName' => 'account.level_1.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountName' => 'account.level_1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountName' => 'account.level_1.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountName' => 'account.level_1.2',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expectedVisibility' => true,
            ]
        ];
    }

    /**
     * @dataProvider getCategoryIdsByVisibilityDataProvider
     * @param int $visibility
     * @param string $accountName
     * @param int $configValue
     * @param array $expected
     */
    public function testGetCategoryIdsByVisibility($visibility, $accountName, $configValue, array $expected)
    {
        /** @var Account $account */
        $account = $this->getReference($accountName);

        $categoryIds = $this->repository->getCategoryIdsByVisibility($visibility, $account, $configValue);

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
                'accountName' => 'account.level_1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1',
                    'category_1_5',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountName' => 'account.level_1',
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
                'accountName' => 'account.level_1.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_5',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountName' => 'account.level_1.1',
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
                'accountName' => 'account.level_1.2',
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
                'accountName' => 'account.level_1.2',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_5_6_7',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'accountName' => 'account.level_1.2.1',
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
                'accountName' => 'account.level_1.2.1',
                'configValue' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'expected' => [
                    'category_1_5_6_7'
                ]
            ],
        ];
    }

    /**
     * @dataProvider updateAccountCategoryVisibilityByCategoryDataProvider
     * @param string $account
     * @param array $categories
     * @param int $visibility
     */
    public function testUpdateAccountCategoryVisibilityByCategory($account, array $categories, $visibility)
    {
        /** @var Account $account */
        $account = $this->getReference($account);

        /** @var Category[] $categoriesForUpdate */
        $categoriesForUpdate = [];
        foreach ($categories as $categoryName) {
            $categoriesForUpdate[] = $this->getReference($categoryName);
        }

        $categoryIdsForUpdate = array_filter(
            $categoriesForUpdate,
            function (Category $category) {
                return $category->getId();
            }
        );

        $this->repository->updateAccountCategoryVisibilityByCategory(
            $account,
            $categoryIdsForUpdate,
            $visibility
        );

        foreach ($categoriesForUpdate as $category) {
            $visibilityResolved = $this->repository->findByPrimaryKey($category, $account);
            $this->assertEquals($visibility, $visibilityResolved->getVisibility());
        }
    }

    /**
     * @return array
     */
    public function updateAccountCategoryVisibilityByCategoryDataProvider()
    {
        return [
            'Change visibility to visible' => [
                'account' => 'account.level_1',
                'categories' => [
                    'category_1',
                    'category_1_5_6',
                    'category_1_5_6_7'
                ],
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE
            ],
            'Change visibility to hidden' => [
                'account' => 'account.level_1.1',
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
        /** @var AccountCategoryVisibilityResolved $actualEntity */
        $actualEntity = $this->repository->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $expectedEntity = $this->repository->findByPrimaryKey(
            $actualEntity->getCategory(),
            $actualEntity->getAccount()
        );

        $this->assertEquals(spl_object_hash($expectedEntity), spl_object_hash($actualEntity));
    }

    public function testInsertStaticValues()
    {
        /** @var AccountCategoryVisibility[] $visibilities */
        $visibilities = $this->getManagerRegistry()
            ->getManagerForClass('OroVisibilityBundle:Visibility\AccountCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\AccountCategoryVisibility')
            ->createQueryBuilder('entity')
            ->andWhere('entity.visibility IN (:scalarVisibilities)')
            ->setParameter(
                'scalarVisibilities',
                [AccountCategoryVisibility::VISIBLE, AccountCategoryVisibility::HIDDEN]
            )
            ->getQuery()
            ->getResult();
        $this->assertNotEmpty($visibilities);

        /** @var AccountCategoryVisibility[] $indexedVisibilities */
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
            $this->assertEquals($visibility->getAccount()->getId(), $resolvedVisibility['account']);
            $this->assertEquals(AccountCategoryVisibilityResolved::SOURCE_STATIC, $resolvedVisibility['source']);
            if ($visibility->getVisibility() === AccountCategoryVisibility::VISIBLE) {
                $this->assertEquals(
                    AccountCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                    $resolvedVisibility['visibility']
                );
            } else {
                $this->assertEquals(
                    AccountCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                    $resolvedVisibility['visibility']
                );
            }
        }
    }

    public function testInsertCategoryValues()
    {
        /** @var AccountCategoryVisibility[] $visibilities */
        $visibilities = $this->getManagerRegistry()
            ->getManagerForClass('OroVisibilityBundle:Visibility\AccountCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\AccountCategoryVisibility')
            ->createQueryBuilder('entity')
            ->andWhere('entity.visibility = :categoryVisibility')
            ->setParameter('categoryVisibility', AccountCategoryVisibility::CATEGORY)
            ->getQuery()
            ->getResult();
        $this->assertNotEmpty($visibilities);

        /** @var AccountCategoryVisibility[] $indexedVisibilities */
        $indexedVisibilities = [];
        foreach ($visibilities as $visibility) {
            $indexedVisibilities[$visibility->getId()] = $visibility;
        }

        $this->repository->clearTable();
        $this->repository->insertCategoryValues($this->getInsertExecutor());

        $resolvedVisibilities = $this->getResolvedVisibilities();

        $this->assertSameSize($indexedVisibilities, $resolvedVisibilities);
        foreach ($resolvedVisibilities as $resolvedVisibility) {
            $id = $resolvedVisibility['sourceCategoryVisibility'];
            $this->assertArrayHasKey($id, $indexedVisibilities);
            $visibility = $indexedVisibilities[$id];

            $this->assertEquals($visibility->getCategory()->getId(), $resolvedVisibility['category']);
            $this->assertEquals($visibility->getAccount()->getId(), $resolvedVisibility['account']);
            $this->assertEquals(AccountCategoryVisibilityResolved::SOURCE_STATIC, $resolvedVisibility['source']);
            $this->assertEquals($visibility->getVisibility(), AccountCategoryVisibility::CATEGORY);
        }
    }

    public function testInsertParentCategoryValues()
    {
        /** @var Account $account */
        $account = $this->getReference('account.level_1.1');

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
            $this->getInsertExecutor(),
            array_merge($parentCategoryVisibilities, $staticCategoryVisibilities),
            $visibility
        );

        $resolvedVisibilities = $this->getResolvedVisibilities();
        $resolvedVisibilities = $this->filterVisibilitiesByAccount($resolvedVisibilities, $account->getId());

        // static visibilities should not be inserted
        $this->assertSameSize($parentCategoryFallbackCategoryIds, $resolvedVisibilities);
        foreach ($resolvedVisibilities as $resolvedVisibility) {
            $this->assertContains($resolvedVisibility['category'], $parentCategoryFallbackCategoryIds);
            $this->assertEquals(CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY, $resolvedVisibility['source']);
            $this->assertEquals($visibility, $resolvedVisibility['visibility']);
        }
    }

    /**
     * @param array $visibilities
     * @param $accountId
     * @return array
     */
    protected function filterVisibilitiesByAccount(array $visibilities, $accountId)
    {
        $currentAccountVisibilities = [];
        foreach ($visibilities as $visibility) {
            if ($visibility['account'] == $accountId) {
                $currentAccountVisibilities[] = $visibility;
            }
        }

        return $currentAccountVisibilities;
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
     * @return AccountCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
    }

    /**
     * @return Category
     */
    protected function getMasterCatalog()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroCatalogBundle:Category')
            ->getRepository('OroCatalogBundle:Category')
            ->getMasterCatalogRoot();
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
                'IDENTITY(entity.account) as account',
                'entity.visibility',
                'entity.source'
            )
            ->getQuery()
            ->getArrayResult();
    }
}
