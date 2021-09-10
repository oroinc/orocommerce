<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\EventListener;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CategoryTreeHandlerListenerTest extends WebTestCase
{
    use CatalogTrait;
    use ConfigManagerAwareTestTrait;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadCustomerUserData::class,
            LoadCategoryVisibilityData::class
        ]);
    }

    /**
     * @dataProvider checkCalculatedCategoriesDataProvider
     */
    public function testCheckCalculatedCategories(array $visibleCategories, array $invisibleCategories)
    {
        $configManager = self::getConfigManager('global');
        $configManager->set('oro_visibility.category_visibility', CategoryVisibility::VISIBLE);
        $configManager->flush();
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')
            ->buildCache();

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
                    'All Products',
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
     * @dataProvider changeCustomerGroupCategoryVisibilityToHiddenDataProvider
     * @param string $categoryToHide
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeCustomerGroupCategoryVisibilityToHidden(
        $categoryToHide,
        array $visibleCategories,
        array $invisibleCategories
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryToHide);
        $this->createCustomerGroupCategoryVisibility($category, CustomerGroupCategoryVisibility::HIDDEN);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    /**
     * @return array
     */
    public function changeCustomerGroupCategoryVisibilityToHiddenDataProvider()
    {
        return [
            [
                'categoryToHide' => 'category_1_5',
                'visibleCategories' => [
                    'All Products',
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
     * @depends testChangeCustomerGroupCategoryVisibilityToHidden
     * @dataProvider changeCustomerGroupCategoryVisibilityToVisibleDataProvider
     * @param string $categoryToShow
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeCustomerGroupCategoryVisibilityToVisible(
        $categoryToShow,
        array $visibleCategories,
        array $invisibleCategories
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryToShow);

        $this->updateCustomerGroupCategoryVisibility($category, CustomerGroupCategoryVisibility::VISIBLE);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    /**
     * @return array
     */
    public function changeCustomerGroupCategoryVisibilityToVisibleDataProvider()
    {
        return [
            [
                'categoryToShow' => 'category_1_2',
                'visibleCategories' => [
                    'All Products',
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
     * @depends testChangeCustomerGroupCategoryVisibilityToVisible
     * @dataProvider changeCustomerCategoryVisibilityToHiddenDataProvider
     * @param string $categoryToShow
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeCustomerCategoryVisibilityToHidden(
        $categoryToShow,
        array $visibleCategories,
        array $invisibleCategories
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryToShow);
        $this->updateCustomerCategoryVisibility($category, CustomerCategoryVisibility::HIDDEN);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    /**
     * @return array
     */
    public function changeCustomerCategoryVisibilityToHiddenDataProvider()
    {
        return [
            [
                'categoryToShow' => 'category_1',
                'visibleCategories' => [
                    'All Products',
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
     * @depends testChangeCustomerCategoryVisibilityToHidden
     * @dataProvider changeCustomerCategoryVisibilityToVisibleDataProvider
     * @param string $categoryToShow
     * @param array $visibleCategories
     * @param array $invisibleCategories
     */
    public function testChangeCustomerCategoryVisibility(
        $categoryToShow,
        array $visibleCategories,
        array $invisibleCategories
    ) {
        /** @var Category $category */
        $category = $this->getReference($categoryToShow);
        $this->updateCustomerCategoryVisibility($category, CustomerCategoryVisibility::VISIBLE);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    /**
     * @return array
     */
    public function changeCustomerCategoryVisibilityToVisibleDataProvider()
    {
        return [
            [
                'categoryToShow' => 'category_1',
                'visibleCategories' => [
                    'All Products',
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
    protected function createCustomerGroupCategoryVisibility(Category $category, $visibility)
    {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility');

        $customerGroupVisibility = new CustomerGroupCategoryVisibility();

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference('customer_group.group1');
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
        $customerGroupVisibility->setScope($scope);
        $customerGroupVisibility->setCategory($category);
        $customerGroupVisibility->setVisibility($visibility);

        $em->persist($customerGroupVisibility);
        $em->flush();
    }

    /**
     * @param Category $category
     * @param string $visibility
     */
    protected function updateCustomerGroupCategoryVisibility(
        Category $category,
        $visibility
    ) {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility');

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference('customer_group.group1');
        /** @var CustomerGroupCategoryVisibility $customerGroupVisibility */
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
        $customerGroupVisibility = $em
            ->getRepository('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility')
            ->findOneBy(
                [
                    'category' => $category,
                    'scope' => $scope
                ]
            );

        $customerGroupVisibility->setVisibility($visibility);

        $em->persist($customerGroupVisibility);
        $em->flush();
    }

    /**
     * @param Category $category
     * @param string $visibility
     */
    protected function updateCustomerCategoryVisibility(Category $category, $visibility)
    {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:Visibility\CustomerCategoryVisibility');

        /** @var Customer $customer */
        $customer = $this->getReference('customer.level_1');
        /** @var CustomerCategoryVisibility $customerVisibility */
        $scope = $this->scopeManager->findOrCreate(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $customer]
        );
        $customerVisibility = $em
            ->getRepository(CustomerCategoryVisibility::class)
            ->findOneBy(['category' => $category, 'scope' => $scope]);

        $customerVisibility->setVisibility($visibility);

        $em->persist($customerVisibility);
        $em->flush();
    }

    /**
     * @return CustomerUser
     */
    protected function getCustomerUser()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass('OroCustomerBundle:CustomerUser')
            ->getRepository('OroCustomerBundle:CustomerUser')
            ->findOneBy(['email' => LoadCustomerUserData::EMAIL]);
    }

    /**
     * @return array
     */
    protected function getActualCategories()
    {
        $customerUser = $this->getCustomerUser();
        $categories = $this->getContainer()
            ->get('oro_catalog.provider.category_tree_provider')
            ->getCategories($customerUser, $this->getRootCategory());

        $categoryTitles = [];
        foreach ($categories as $category) {
            $categoryTitles[] = $category->getDefaultTitle()->getString();
        }

        return $categoryTitles;
    }

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
