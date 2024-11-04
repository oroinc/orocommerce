<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Layout\DataProvider\FeaturedCategoriesProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\LocaleBundle\Tests\Unit\Entity\Stub\Localization;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\Constraint\IsType;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class FeaturedCategoriesProviderTest extends \PHPUnit\Framework\TestCase
{
    private const LIFETIME = 4242;

    private CategoryTreeProvider|\PHPUnit\Framework\MockObject\MockObject $categoryTreeProvider;
    private TokenAccessor|\PHPUnit\Framework\MockObject\MockObject $tokenAccessor;
    private AbstractAdapter|\PHPUnit\Framework\MockObject\MockObject $cache;
    private WebsiteManager|\PHPUnit\Framework\MockObject\MockObject $websiteManager;
    private FeaturedCategoriesProvider $featuredCategoriesProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->categoryTreeProvider = $this->createMock(CategoryTreeProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);

        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $localization = new Localization();
        $localization->setId(1);

        $localizationHelper->expects(self::any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->featuredCategoriesProvider = new FeaturedCategoriesProvider(
            $this->categoryTreeProvider,
            $this->tokenAccessor,
            $localizationHelper,
            $this->websiteManager
        );

        $this->cache = $this->createMock(AbstractAdapter::class);
        $this->featuredCategoriesProvider->setCache($this->cache, self::LIFETIME);
    }

    private function getCategory(int $id, int $level): Category
    {
        $category = new Category();
        ReflectionUtil::setId($category, $id);
        $category->setLevel($level);

        return $category;
    }

    private function getCustomerUser(int $id): CustomerUser
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, $id);

        return $customerUser;
    }

    private function getWebsite(int $id): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, $id);

        return $website;
    }

    private function getOrganization(int $id): Organization
    {
        $organization = new Organization();
        ReflectionUtil::setId($organization, $id);

        return $organization;
    }

    /**
     * @dataProvider categoriesDataProvider
     */
    public function testGetAll(array $categories, array $categoryIds, array $result): void
    {
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->cache->expects(self::once())
            ->method('get')
            ->with(
                'featured_categories_100_1_0_0__' . implode('_', $categoryIds) . '__7_0',
                new IsType(IsType::TYPE_CALLABLE)
            )
            ->willReturnCallback(function (string $key, callable $callback) {
                $cacheItem = $this->createMock(ItemInterface::class);
                $cacheItem->expects(self::once())
                    ->method('expiresAfter')
                    ->with(self::LIFETIME);

                return $callback($cacheItem);
            });

        $user = $this->getCustomerUser(100);
        $organization = $this->getOrganization(7);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->categoryTreeProvider->expects(self::once())
            ->method('getCategories')
            ->with($user)
            ->willReturn($categories);

        $actual = $this->featuredCategoriesProvider->getAll($categoryIds);
        self::assertEquals($result, $actual);
    }

    public function categoriesDataProvider(): array
    {
        return [
            'level is equal zero' => [
                'data' => [
                    $this->getCategory(1, 0)
                ],
                'categoryIds' => [1],
                'result' => []
            ],
            'not in list of category ids' => [
                'data' => [
                    $this->getCategory(1, 1)
                ],
                'categoryIds' => [2],
                'result' => []
            ],
            'one proper category in list' => [
                'data' => [
                    $this->getCategory(1, 1),
                    $this->getCategory(2, 0),
                    $this->getCategory(3, 1)
                ],
                'categoryIds' => [1, 2],
                'result' => [
                    ['id' => 1, 'title' => '', 'small_image' => null, 'short' => '']
                ]
            ]
        ];
    }

    public function testGetAllCached(): void
    {
        $result = ['id' => 1, 'title' => '', 'small_image' => null];

        $website = $this->getWebsite(42);
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->cache->expects(self::once())
            ->method('get')
            ->with('featured_categories_100_1_0_0__1__7_42')
            ->willReturn($result);

        $user = $this->getCustomerUser(100);
        $organization = $this->getOrganization(7);

        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor->expects(self::once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->categoryTreeProvider->expects(self::never())
            ->method('getCategories');

        $actual = $this->featuredCategoriesProvider->getAll([1]);
        self::assertEquals($result, $actual);
    }
}
