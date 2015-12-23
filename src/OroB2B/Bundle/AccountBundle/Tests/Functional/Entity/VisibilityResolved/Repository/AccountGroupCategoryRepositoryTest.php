<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Entity\VisibilityResolved\Repository;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 */
class AccountGroupCategoryRepositoryTest extends WebTestCase
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
     * @param string $accountGroupName
     * @param bool $expectedVisibility
     */
    public function testIsCategoryVisible($categoryName, $accountGroupName, $expectedVisibility)
    {
        /** @var Category $category */
        $category = $this->getReference($categoryName);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroupName);

        $actualVisibility = $this->getRepository()->isCategoryVisible($category, $accountGroup);

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
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1',
                'accountGroupName' => 'account_group.group3',
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2',
                'accountGroupName' => 'account_group.group1',
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2',
                'accountGroupName' => 'account_group.group3',
                'expectedVisibility' => false,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountGroupName' => 'account_group.group1',
                'expectedVisibility' => true,
            ],
            [
                'categoryName' => 'category_1_2_3',
                'accountGroupName' => 'account_group.group3',
                'expectedVisibility' => false,
            ],
        ];
    }

    /**
     * @dataProvider getCategoryIdsByVisibilityDataProvider
     * @param int $visibility
     * @param string $accountGroupName
     * @param array $expected
     */
    public function testGetCategoryIdsByVisibility($visibility, $accountGroupName, array $expected)
    {
        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference($accountGroupName);

        $categoryIds = $this->getRepository()->getCategoryIdsByVisibility($visibility, $accountGroup);

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
                'accountGroupName' => 'account_group.group1',
                'expected' => [
                    'category_1',
                    'category_1_2_3',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountGroupName' => 'account_group.group1',
                'expected' => [
                    'category_1_2',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'accountGroupName' => 'account_group.group3',
                'expected' => [
                    'category_1',
                ]
            ],
            [
                'visibility' => BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'accountGroupName' => 'account_group.group3',
                'expected' => [
                    'category_1_2',
                    'category_1_2_3',
                ]
            ],
        ];
    }

    /**
     * @return AccountGroupCategoryRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroB2BAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
    }
}
