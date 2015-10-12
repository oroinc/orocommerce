<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Functional\Calculator;

use Oro\Component\Testing\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CategoryVisibilityCalculatorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::EMAIL, LoadAccountUserData::PASSWORD)
        );

        $this->loadFixtures(['OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData']);
    }

    /**
     * @dataProvider checkCalculatedCategoriesDataProvider
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testCheckCalculatedCategories(array $visibleCategories, array $invisibleCategories)
    {
        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    /**
     * @return array
     */
    public function checkCalculatedCategoriesDataProvider()
    {
        return [
            [
                'visibleCategories' => [
                    'category_1_2',
                    'category_1_2_6',
                    'category_1_2_6_8',
                    'category_1_2_9',
                    'category_1_10',
                ],
                'invisibleCategories' => [
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_2_3_5',
                    'category_1_2_6_7'
                ]
            ]
        ];
    }

    /**
     * @dataProvider changeAccountGroupCategoryVisibilityToHiddenDataProvider
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeAccountGroupCategoryVisibilityToHidden(
        array $visibleCategories,
        array $invisibleCategories
    ) {
        /** @var Category $category */
        $category = $this->getReference('category_1_2_6');
        $this->createAccountGroupCategoryVisibility($category, AccountGroupCategoryVisibility::HIDDEN);

        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    /**
     * @return array
     */
    public function changeAccountGroupCategoryVisibilityToHiddenDataProvider()
    {
        return [
            [
                'visibleCategories' => [
                    'category_1_2',
                    'category_1_2_9',
                    'category_1_10',
                ],
                'invisibleCategories' => [
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_2_3_5',
                    'category_1_2_6',
                    'category_1_2_6_8',
                    'category_1_2_6_7'
                ]
            ]
        ];
    }

    /**
     * @depends testChangeAccountGroupCategoryVisibilityToHidden
     * @dataProvider changeAccountGroupCategoryVisibilityToVisibleDataProvider
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeAccountGroupCategoryVisibilityToVisible(
        array $visibleCategories,
        array $invisibleCategories
    ) {
        /** @var Category $category */
        $category = $this->getReference('category_1_2_6');

        $this->updateAccountGroupCategoryVisibility($category, AccountGroupCategoryVisibility::VISIBLE);

        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    /**
     * @return array
     */
    public function changeAccountGroupCategoryVisibilityToVisibleDataProvider()
    {
        return [
            [
                'visibleCategories' => [
                    'category_1_2',
                    'category_1_2_6',
                    'category_1_2_6_8',
                    'category_1_2_9',
                    'category_1_10',
                ],
                'invisibleCategories' => [
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_2_3_5',
                    'category_1_2_6_7'
                ]
            ]
        ];
    }

    /**
     * @dataProvider changeAccountCategoryVisibilityDataProvider
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeAccountCategoryVisibility(array $visibleCategories, array $invisibleCategories)
    {
        /** @var Category $category */
        $category = $this->getReference('category_1_10');
        $this->updateAccountCategoryVisibility($category, AccountCategoryVisibility::HIDDEN);

        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    /**
     * @return array
     */
    public function changeAccountCategoryVisibilityDataProvider()
    {
        return [
            [
                'visibleCategories' => [
                    'category_1_2',
                    'category_1_2_6',
                    'category_1_2_6_8',
                    'category_1_2_9'
                ],
                'invisibleCategories' => [
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_2_3_5',
                    'category_1_2_6_7',
                    'category_1_10'
                ]
            ]
        ];
    }

    /**
     * @param Category $category
     * @param string $visibility
     */
    protected function createAccountGroupCategoryVisibility(Category $category, $visibility)
    {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility');

        $accountGroupVisibility = new AccountGroupCategoryVisibility();

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');
        $accountGroupVisibility->setAccountGroup($accountGroup);
        $accountGroupVisibility->setCategory($category);
        $accountGroupVisibility->setVisibility($visibility);

        $em->persist($accountGroupVisibility);
        $em->flush();
    }

    /**
     * @param Category $category
     * @param string $visibility
     */
    protected function updateAccountGroupCategoryVisibility(
        Category $category,
        $visibility
    ) {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility');

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');

        $accountGroupVisibility = $em
            ->getRepository('OroB2BAccountBundle:Visibility\AccountGroupCategoryVisibility')
            ->findOneBy(
                [
                    'category' => $category,
                    'accountGroup' => $accountGroup
                ]
            );

        $accountGroupVisibility->setVisibility($visibility);

        $em->persist($accountGroupVisibility);
        $em->flush();
    }

    /**
     * @param Category $category
     * @param string $visibility
     */
    protected function updateAccountCategoryVisibility(Category $category, $visibility)
    {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroB2BAccountBundle:Visibility\AccountCategoryVisibility');

        /** @var Account $account */
        $account = $this->getReference('account.level_1.3');
        $accountVisibility = $em
            ->getRepository('OroB2BAccountBundle:Visibility\AccountCategoryVisibility')
            ->findOneBy(['category' => $category, 'account' => $account]);

        $accountVisibility->setVisibility($visibility);

        $em->persist($accountVisibility);
        $em->flush();
    }

    /**
     * @return string
     */
    protected function getTreeData()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_frontend_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->filter('.category.b2b-tree div')->attr('data-page-component-options');
    }

    /**
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    protected function assertTreeCategories(array $visibleCategories, array $invisibleCategories)
    {
        $treeData = $this->getTreeData();

        $treeCategoryIds = array_map(
            function ($data) {
                return $data->text;
            },
            json_decode($treeData)->data
        );

        foreach ($visibleCategories as $categoryName) {
            $this->assertContains($categoryName, $treeCategoryIds);
        }

        foreach ($invisibleCategories as $categoryName) {
            $this->assertNotContains($categoryName, $treeCategoryIds);
        }
    }
}
