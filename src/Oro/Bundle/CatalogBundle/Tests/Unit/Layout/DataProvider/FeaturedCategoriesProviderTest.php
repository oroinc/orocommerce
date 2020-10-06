<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\FeaturedCategoriesProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Component\Testing\Unit\EntityTrait;

class FeaturedCategoriesProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CategoryTreeProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $categoryTreeProvider;

    /**
     * @var TokenAccessor|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenAccessor;

    /**
     * @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localizationHelper;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var FeaturedCategoriesProvider
     */
    protected $featuredCategoriesProvider;

    protected function setUp(): void
    {
        $this->categoryTreeProvider = $this->getMockBuilder(CategoryTreeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenAccessor = $this->createMock(TokenAccessor::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->featuredCategoriesProvider = new FeaturedCategoriesProvider(
            $this->categoryTreeProvider,
            $this->tokenAccessor,
            $this->localizationHelper
        );

        $this->cache = $this->createMock(CacheProvider::class);
        $this->featuredCategoriesProvider->setCache($this->cache);
    }

    /**
     * @dataProvider categoriesDataProvider
     *
     * @param array $data
     * @param array $categoryIds
     * @param array $result
     */
    public function testGetAll($data, $categoryIds, $result)
    {
        $categories = [];
        foreach ($data as $categoryData) {
            $categories[] = $this->getEntity(Category::class, $categoryData);
        }

        $this->cache->expects($this->once())
            ->method('fetch')
            ->willReturn(false);

        $user = new CustomerUser();
        $organization = $this->getEntity(Organization::class, ['id' => 7]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->categoryTreeProvider->expects($this->once())
            ->method('getCategories')
            ->with($user)
            ->willReturn($categories);

        $this->cache->expects($this->once())
            ->method('save');

        $actual = $this->featuredCategoriesProvider->getAll($categoryIds);
        $this->assertEquals($result, $actual);
    }

    public function testGetAllCached()
    {
        $result = ['id' => 1, 'title' => '', 'small_image' => null];

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('featured_categories__0_0_0_1_7')
            ->willReturn($result);

        $user = new CustomerUser();
        $organization = $this->getEntity(Organization::class, ['id' => 7]);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);

        $this->categoryTreeProvider->expects($this->never())
            ->method('getCategories');

        $this->cache->expects($this->never())
            ->method('save');

        $actual = $this->featuredCategoriesProvider->getAll([1]);
        $this->assertEquals($result, $actual);
    }

    /**
     * @return array
     */
    public function categoriesDataProvider()
    {
        return [
            'level is equal zero' => [
                'data' => [
                    ['id' => 1, 'level' => 0],
                ],
                'categoryIds' => [1],
                'result' => [],
            ],
            'not in list of category ids' => [
                'data' => [
                    ['id' => 1, 'level' => 1],
                ],
                'categoryIds' => [2],
                'result' => [],
            ],
            'one proper category in list' => [
                'data' => [
                    ['id' => 1, 'level' => 1],
                    ['id' => 2, 'level' => 0],
                    ['id' => 3, 'level' => 1],
                ],
                'categoryIds' => [1, 2],
                'result' => [
                    ['id' => 1, 'title' => '', 'small_image' => null, 'short' => '']
                ]
            ],
        ];
    }
}
