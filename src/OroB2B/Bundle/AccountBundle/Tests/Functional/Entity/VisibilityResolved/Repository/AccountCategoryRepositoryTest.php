<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
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

        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityResolvedData'
        ]);
    }

    /**
     * @dataProvider isCategoryVisibleDataProvider
     * @param string $categoryName
     * @param string $accountName
     * @param bool $expectedVisibility
     */
    public function testIsCategoryVisible($categoryName, $accountName, $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        /** @var Account $account */
        $account = $this->getReference($accountName);

        $actualVisibility = $this->getRepository()->isCategoryVisible($category, $account);

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
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1',
                'accountName' => 'account.level_1.1',
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1',
                'accountName' => 'account.level_1.2',
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'accountName' => 'account.level_1',
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2',
                'accountName' => 'account.level_1.1',
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountName' => 'account.level_1',
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountName' => 'account.level_1.1',
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountName' => 'account.level_1.2',
                'expectedVisibility' => true,
            ]
        ];
    }

    /**
     * @dataProvider getCategoryIdsByVisibilityDataProvider
     * @param int $visibility
     * @param string $accountName
     * @param array $expected
     */
    public function testGetCategoryIdsByVisibility($visibility, $accountName, array $expected)
    {
        /** @var Account $account */
        $account = $this->getReference($accountName);

        $categoryIds = $this->getRepository()->getCategoryIdsByVisibility($visibility, $account);

        $expectedCategoryIds = [];
        foreach ($expected as $categoryName) {
            /** @var Category $category */
            $category = $this->getReference($categoryName);
            $expectedCategoryIds[] = $category->getId();
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
                'expected' => [
                    'category_1',
                    'category_1_2_3',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountName' => 'account.level_1',
                'expected' => [
                    'category_1_2',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'accountName' => 'account.level_1.1',
                'expected' => [
                    'category_1',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountName' => 'account.level_1.1',
                'expected' => [
                    'category_1_2',
                    'category_1_2_3',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'accountName' => 'account.level_1.2',
                'expected' => [
                    'category_1',
                    'category_1_2_3',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountName' => 'account.level_1.2',
                'expected' => [
                    'category_1_2',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'accountName' => 'account.level_1.2.1',
                'expected' => [
                    'category_1',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountName' => 'account.level_1.2.1',
                'expected' => [
                    'category_1_2',
                    'category_1_2_3',
                ]
            ],
        ];
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
}
