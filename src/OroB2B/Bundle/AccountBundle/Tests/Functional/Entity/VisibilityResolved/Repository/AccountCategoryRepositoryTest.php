<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\AccountCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class AccountCategoryRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData']);
        // TODO: remove cache generation in scope of BB-1803
        $this->getContainer()->get('orob2b_account.visibility.cache.product.category.cache_builder')->buildCache();
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

        $actualVisibility = $this->getRepository()->isCategoryVisible($category, $account, $configValue);

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

        $categoryIds = $this->getRepository()->getCategoryIdsByVisibility($visibility, $account, $configValue);

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

        $this->getRepository()->updateAccountCategoryVisibilityByCategory(
            $account,
            $categoryIdsForUpdate,
            $visibility
        );

        foreach ($categoriesForUpdate as $category) {
            $visibilityResolved = $this->getRepository()->findByPrimaryKey($category, $account);
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
        $actualEntity = $this->getRepository()->findOneBy([]);
        if (!$actualEntity) {
            $this->markTestSkipped('Can\'t test method because fixture was not loaded.');
        }

        $expectedEntity = $this->getRepository()->findByPrimaryKey(
            $actualEntity->getCategory(),
            $actualEntity->getAccount()
        );

        $this->assertEquals(spl_object_hash($expectedEntity), spl_object_hash($actualEntity));
    }

    /**
     * @return AccountCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
    }

    /**
     * @return Category
     */
    protected function getMasterCatalog()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BCatalogBundle:Category')
            ->getRepository('OroB2BCatalogBundle:Category')
            ->getMasterCatalogRoot();
    }
}
