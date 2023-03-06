<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Menu;

use Oro\Bundle\CatalogBundle\Menu\MenuCategoriesCachingProvider;
use Oro\Bundle\CatalogBundle\Menu\MenuCategoriesProviderInterface;
use Oro\Bundle\CatalogBundle\Tests\Unit\Stub\CategoryStub;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Tests\Unit\Fixtures\Entity\Customer;
use Oro\Bundle\CustomerBundle\Tests\Unit\Stub\CustomerUserStub;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TaxBundle\Tests\Unit\Entity\CustomerGroupStub;
use Oro\Bundle\UserBundle\Entity\UserInterface;
use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class MenuCategoriesCachingProviderTest extends \PHPUnit\Framework\TestCase
{
    private MenuCategoriesProviderInterface|\PHPUnit\Framework\MockObject\MockObject $menuCategoriesProvider;

    private TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject $tokenAccessor;

    private ArrayAdapter $cache;

    protected function setUp(): void
    {
        $this->menuCategoriesProvider = $this->createMock(MenuCategoriesProviderInterface::class);
        $customerUserRelationsProvider = $this->createMock(CustomerUserRelationsProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->cache = new ArrayAdapter();

        $this->provider = new MenuCategoriesCachingProvider(
            $this->menuCategoriesProvider,
            $customerUserRelationsProvider,
            $this->tokenAccessor
        );

        $customerUserRelationsProvider
            ->expects(self::any())
            ->method('getCustomer')
            ->willReturnCallback(static fn ($customerUser) => $customerUser->getCustomer());

        $customerUserRelationsProvider
            ->expects(self::any())
            ->method('getCustomerGroup')
            ->willReturnCallback(static fn ($customerUser) => $customerUser->getCustomer()?->getGroup());
    }

    /**
     * @dataProvider getCategoriesDataProvider
     */
    public function testGetCategories(
        ?UserInterface $user,
        ?int $organizationId,
        array $context,
        string $expectedCacheKey
    ): void {
        $category = (new CategoryStub())->setId(50);

        $this->tokenAccessor
            ->expects(self::any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor
            ->expects(self::any())
            ->method('getOrganizationId')
            ->willReturn($organizationId);

        $categoriesData = [[42 => ['id' => 42, 'title' => 'sample', 'parentId' => 0, 'level' => 0]]];
        $this->menuCategoriesProvider
            ->expects(self::once())
            ->method('getCategories')
            ->with($category, $user, $context)
            ->willReturn($categoriesData);

        $this->provider->setCache($this->cache, 3600);
        self::assertEquals($categoriesData, $this->provider->getCategories($category, $user, $context));

        self::assertTrue($this->cache->hasItem($expectedCacheKey));

        // Checks cache.
        self::assertEquals($categoriesData, $this->provider->getCategories($category, $user, $context));
    }

    public function getCategoriesDataProvider(): array
    {
        $user = new CustomerUserStub(10);
        $customer = (new Customer())->setId(20);
        $customerGroup = new CustomerGroupStub(30);
        $user->setCustomer($customer);
        $customer->setGroup($customerGroup);
        $organizationId = 60;
        $userWithoutCustomerGroup = new CustomerUserStub(10);
        $userWithoutCustomerGroup->setCustomer((new Customer())->setId(20));

        return [
            'all parameters' => [
                'user' => $user,
                'organizationId' => $organizationId,
                'context' => ['tree_depth' => 3],
                'expectedCacheKey' => 'menu_category_50_3_10_20_30_60',
            ],
            'no organization' => [
                'user' => $user,
                'organizationId' => null,
                'context' => ['tree_depth' => 3],
                'expectedCacheKey' => 'menu_category_50_3_10_20_30_0',
            ],
            'no tree depth' => [
                'user' => $user,
                'organizationId' => null,
                'context' => [],
                'expectedCacheKey' => 'menu_category_50_-1_10_20_30_0',
            ],
            'no customer group' => [
                'user' => $userWithoutCustomerGroup,
                'organizationId' => null,
                'context' => [],
                'expectedCacheKey' => 'menu_category_50_-1_10_20_0_0',
            ],
            'no customer' => [
                'user' => new CustomerUserStub(10),
                'organizationId' => null,
                'context' => [],
                'expectedCacheKey' => 'menu_category_50_-1_10_0_0_0',
            ],
            'user instead of customer' => [
                'user' => new UserStub(100),
                'organizationId' => null,
                'context' => [],
                'expectedCacheKey' => 'menu_category_50_-1_100_0_0_0',
            ],
        ];
    }

    public function testGetCategoriesWhenNoParameters(): void
    {
        $category = (new CategoryStub())->setId(50);

        $categoriesData = [[42 => ['id' => 42, 'title' => 'sample', 'parentId' => 0, 'level' => 0]]];
        $this->menuCategoriesProvider
            ->expects(self::once())
            ->method('getCategories')
            ->with($category, null)
            ->willReturn($categoriesData);

        $this->provider->setCache($this->cache, 3600);
        self::assertEquals($categoriesData, $this->provider->getCategories($category));

        $expectedCacheKey = 'menu_category_50_-1_0_0_0_0';
        self::assertTrue($this->cache->hasItem($expectedCacheKey));

        // Checks cache.
        self::assertEquals($categoriesData, $this->provider->getCategories($category));
    }

    public function testGetCategoriesWhenCacheLifetime0InContext(): void
    {
        $category = (new CategoryStub())->setId(50);

        $categoriesData = [[42 => ['id' => 42, 'title' => 'sample', 'parentId' => 0, 'level' => 0]]];
        $this->menuCategoriesProvider
            ->expects(self::once())
            ->method('getCategories')
            ->with($category, null)
            ->willReturn($categoriesData);

        $this->provider->setCache($this->cache, 3600);
        self::assertEquals(
            $categoriesData,
            $this->provider->getCategories($category, null, ['cache_lifetime' => 0])
        );

        $expectedCacheKey = 'menu_category_50_-1_0_0_0_0';
        self::assertFalse($this->cache->hasItem($expectedCacheKey));
    }

    public function testGetCategoriesWhenCacheLifetime0(): void
    {
        $category = (new CategoryStub())->setId(50);

        $categoriesData = [[42 => ['id' => 42, 'title' => 'sample', 'parentId' => 0, 'level' => 0]]];
        $this->menuCategoriesProvider
            ->expects(self::once())
            ->method('getCategories')
            ->with($category, null)
            ->willReturn($categoriesData);

        $this->provider->setCache($this->cache, 0);
        self::assertEquals($categoriesData, $this->provider->getCategories($category));

        $expectedCacheKey = 'menu_category_50_-1_0_0_0_0';
        self::assertFalse($this->cache->hasItem($expectedCacheKey));
    }
}
