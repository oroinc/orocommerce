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

    private ScopeManager $scopeManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->scopeManager = self::getContainer()->get('oro_scope.scope_manager');
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadCustomerUserData::class,
            LoadCategoryVisibilityData::class
        ]);
    }

    /**
     * @dataProvider checkCalculatedCategoriesDataProvider
     */
    public function testCheckCalculatedCategories(array $visibleCategories, array $invisibleCategories): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_visibility.category_visibility', CategoryVisibility::VISIBLE);
        $configManager->flush();
        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')
            ->buildCache();

        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    public function checkCalculatedCategoriesDataProvider(): array
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
     */
    public function testChangeCustomerGroupCategoryVisibilityToHidden(
        string $categoryToHide,
        array $visibleCategories,
        array $invisibleCategories
    ): void {
        /** @var Category $category */
        $category = $this->getReference($categoryToHide);
        $this->createCustomerGroupCategoryVisibility($category, CustomerGroupCategoryVisibility::HIDDEN);
        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    public function changeCustomerGroupCategoryVisibilityToHiddenDataProvider(): array
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
     */
    public function testChangeCustomerGroupCategoryVisibilityToVisible(
        string $categoryToShow,
        array $visibleCategories,
        array $invisibleCategories
    ): void {
        /** @var Category $category */
        $category = $this->getReference($categoryToShow);

        $this->updateCustomerGroupCategoryVisibility($category, CustomerGroupCategoryVisibility::VISIBLE);
        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    public function changeCustomerGroupCategoryVisibilityToVisibleDataProvider(): array
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
     */
    public function testChangeCustomerCategoryVisibilityToHidden(
        string $categoryToShow,
        array $visibleCategories,
        array $invisibleCategories
    ): void {
        /** @var Category $category */
        $category = $this->getReference($categoryToShow);
        $this->updateCustomerCategoryVisibility($category, CustomerCategoryVisibility::HIDDEN);
        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    public function changeCustomerCategoryVisibilityToHiddenDataProvider(): array
    {
        return [
            [
                'categoryToShow' => 'category_1',
                'visibleCategories' => [
                    'All Products',
                    'category_1_2',
                    'category_1_2_3',
                    'category_1_2_3_4',
                ],
                'invisibleCategories' => [
                    'category_1',
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
     */
    public function testChangeCustomerCategoryVisibility(
        string $categoryToShow,
        array $visibleCategories,
        array $invisibleCategories
    ): void {
        /** @var Category $category */
        $category = $this->getReference($categoryToShow);
        $this->updateCustomerCategoryVisibility($category, CustomerCategoryVisibility::VISIBLE);
        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
        $this->assertTreeCategories($visibleCategories, $invisibleCategories);
    }

    public function changeCustomerCategoryVisibilityToVisibleDataProvider(): array
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

    private function createCustomerGroupCategoryVisibility(Category $category, string $visibility): void
    {
        $em = self::getContainer()->get('doctrine')
            ->getManagerForClass(CustomerGroupCategoryVisibility::class);

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

    private function updateCustomerGroupCategoryVisibility(Category $category, string $visibility): void
    {
        $em = self::getContainer()->get('doctrine')
            ->getManagerForClass(CustomerGroupCategoryVisibility::class);

        /** @var CustomerGroup $customerGroup */
        $customerGroup = $this->getReference('customer_group.group1');
        /** @var CustomerGroupCategoryVisibility $customerGroupVisibility */
        $scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $customerGroup]
        );
        $customerGroupVisibility = $em
            ->getRepository(CustomerGroupCategoryVisibility::class)
            ->findOneBy(['category' => $category, 'scope' => $scope]);

        $customerGroupVisibility->setVisibility($visibility);

        $em->persist($customerGroupVisibility);
        $em->flush();
    }

    private function updateCustomerCategoryVisibility(Category $category, string $visibility): void
    {
        $em = self::getContainer()->get('doctrine')
            ->getManagerForClass(CustomerCategoryVisibility::class);

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

    private function getCustomerUser(): CustomerUser
    {
        return self::getContainer()->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['email' => LoadCustomerUserData::EMAIL]);
    }

    private function getActualCategories(): array
    {
        $customerUser = $this->getCustomerUser();
        $categories = self::getContainer()
            ->get('oro_catalog.provider.category_tree_provider')
            ->getCategories($customerUser, $this->getRootCategory());

        $categoryTitles = [];
        foreach ($categories as $category) {
            $categoryTitles[] = $category->getDefaultTitle()->getString();
        }

        return $categoryTitles;
    }

    private function assertTreeCategories(array $visibleCategories, array $invisibleCategories): void
    {
        $treeCategories = $this->getActualCategories();

        self::assertCount(count($visibleCategories), $treeCategories);

        foreach ($visibleCategories as $categoryName) {
            self::assertContains($categoryName, $treeCategories);
        }

        foreach ($invisibleCategories as $categoryName) {
            self::assertNotContains($categoryName, $treeCategories);
        }
    }
}
