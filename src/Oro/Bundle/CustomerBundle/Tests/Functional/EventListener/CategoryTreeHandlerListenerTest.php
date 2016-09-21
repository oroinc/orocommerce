<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CategoryTreeHandlerListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadAccountUserData',
            'Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData',
        ]);
    }

    /**
     * @dataProvider checkCalculatedCategoriesDataProvider
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testCheckCalculatedCategories(array $visibleCategories, array $invisibleCategories)
    {
        $this->getContainer()->get('oro_account.visibility.cache.cache_builder')->buildCache();
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
                    'Master catalog',
                    'category_1',
                    'category_1_5',
                ],
                'invisibleCategories' => [
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                    'category_1_5_6',
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
        $this->getContainer()->get('oro_account.visibility.cache.cache_builder')->buildCache();
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
                    'Master catalog',
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
        $this->getContainer()->get('oro_account.visibility.cache.cache_builder')->buildCache();
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
                    'Master catalog',
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                ],
                'invisibleCategories' => [
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
        $this->getContainer()->get('oro_account.visibility.cache.cache_builder')->buildCache();
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
                    'Master catalog',
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
        $this->getContainer()->get('oro_account.visibility.cache.cache_builder')->buildCache();
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
                    'Master catalog',
                    'category_1',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                ],
                'invisibleCategories' => [
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
            ->getManagerForClass('OroCustomerBundle:Visibility\AccountGroupCategoryVisibility');

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
            ->getManagerForClass('OroCustomerBundle:Visibility\AccountGroupCategoryVisibility');

        /** @var AccountGroup $accountGroup */
        $accountGroup = $this->getReference('account_group.group1');
        /** @var AccountGroupCategoryVisibility $accountGroupVisibility */
        $accountGroupVisibility = $em
            ->getRepository('OroCustomerBundle:Visibility\AccountGroupCategoryVisibility')
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
            ->getManagerForClass('OroCustomerBundle:Visibility\AccountCategoryVisibility');

        /** @var Account $account */
        $account = $this->getReference('account.level_1');
        /** @var AccountCategoryVisibility $accountVisibility */
        $accountVisibility = $em
            ->getRepository('OroCustomerBundle:Visibility\AccountCategoryVisibility')
            ->findOneBy(['category' => $category, 'account' => $account]);

        $accountVisibility->setVisibility($visibility);

        $em->persist($accountVisibility);
        $em->flush();
    }

    /**
     * @return AccountUser
     */
    protected function getAccountUser()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:AccountUser')
            ->getRepository('OroCustomerBundle:AccountUser')
            ->findOneBy(['email' => LoadAccountUserData::EMAIL]);
    }

    /**
     * @return array
     */
    protected function getActualCategories()
    {
        $accountUser = $this->getAccountUser();
        $categories = $this->getContainer()
            ->get('oro_catalog.provider.category_tree_provider')
            ->getCategories($accountUser);

        $categoryTitles = [];
        foreach ($categories as $category) {
            $categoryTitles[] = $category->getDefaultTitle()->getString();
        }

        return $categoryTitles;
    }

    /**
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    protected function assertTreeCategories(array $visibleCategories, array $invisibleCategories)
    {
        $treeCategories = $this->getActualCategories();

        $this->assertCount(count($visibleCategories), $treeCategories);

        foreach ($visibleCategories as $categoryName) {
            $this->assertContains($categoryName, $treeCategories);
        }

        foreach ($invisibleCategories as $categoryName) {
            $this->assertNotContains($categoryName, $treeCategories);
        }
    }
}
