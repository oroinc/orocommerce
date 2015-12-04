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

        $this->loadFixtures([
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'OroB2B\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
        ]);
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
                    'Product Catalog',
                    'category_1',
                    'category_1_5',
                    'category_1_5_6',
                ],
                'invisibleCategories' => [
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5_6_7',
                ]
            ]
        ];
    }

    /**
     * @dataProvider changeAccountGroupCategoryVisibilityToHiddenDataProvider
     * @param string $categoryToHide
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeAccountGroupCategoryVisibilityToHidden(
        $categoryToHide,
        array $visibleCategories,
        array $invisibleCategories
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryToHide);
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
                'categoryToHide' => 'category_1_5',
                'visibleCategories' => [
                    'Product Catalog',
                    'category_1',
                ],
                'invisibleCategories' => [
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5',
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ]
        ];
    }

    /**
     * @depends testChangeAccountGroupCategoryVisibilityToHidden
     * @dataProvider changeAccountGroupCategoryVisibilityToVisibleDataProvider
     * @param string $categoryToShow
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeAccountGroupCategoryVisibilityToVisible(
        $categoryToShow,
        array $visibleCategories,
        array $invisibleCategories
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryToShow);

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
                'categoryToShow' => 'category_1_2',
                'visibleCategories' => [
                    'Product Catalog',
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                ],
                'invisibleCategories' => [
                    'category_1_2_3_4',
                    'category_1_5',
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ]
        ];
    }

    /**
     * @depends testChangeAccountGroupCategoryVisibilityToVisible
     * @dataProvider changeAccountCategoryVisibilityToHiddenDataProvider
     * @param string $categoryToShow
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeAccountCategoryVisibilityToHidden(
        $categoryToShow,
        array $visibleCategories,
        array $invisibleCategories
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryToShow);
        $this->updateAccountCategoryVisibility($category, AccountCategoryVisibility::HIDDEN);

        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    /**
     * @return array
     */
    public function changeAccountCategoryVisibilityToHiddenDataProvider()
    {
        return [
            [
                'categoryToShow' => 'category_1',
                'visibleCategories' => [
                    'Product Catalog',
                ],
                'invisibleCategories' => [
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5',
                    'category_1_5_6',
                    'category_1_5_6_7',
                ]
            ]
        ];
    }

    /**
     * @depends testChangeAccountCategoryVisibilityToHidden
     * @dataProvider changeAccountCategoryVisibilityToVisibleDataProvider
     * @param string $categoryToShow
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeAccountCategoryVisibility(
        $categoryToShow,
        array $visibleCategories,
        array $invisibleCategories
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryToShow);
        $this->updateAccountCategoryVisibility($category, AccountCategoryVisibility::VISIBLE);

        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    /**
     * @return array
     */
    public function changeAccountCategoryVisibilityToVisibleDataProvider()
    {
        return [
            [
                'categoryToShow' => 'category_1',
                'visibleCategories' => [
                    'Product Catalog',
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                ],
                'invisibleCategories' => [
                    'category_1_2_3_4',
                    'category_1_5',
                    'category_1_5_6',
                    'category_1_5_6_7',
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
        $account = $this->getReference('account.level_1');
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

        $treeCategories = array_map(
            function ($data) {
                return $data->text;
            },
            json_decode($treeData)->data
        );

        $this->assertCount(count($visibleCategories), $treeCategories);

        foreach ($visibleCategories as $categoryName) {
            $this->assertContains($categoryName, $treeCategories);
        }

        foreach ($invisibleCategories as $categoryName) {
            $this->assertNotContains($categoryName, $treeCategories);
        }
    }
}
