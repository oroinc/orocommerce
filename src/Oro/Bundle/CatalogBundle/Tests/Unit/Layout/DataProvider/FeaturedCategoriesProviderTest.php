<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\FeaturedCategoriesProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Tests\Unit\Entity\Stub\Category;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FeaturedCategoriesProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CategoryTreeProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $categoryTreeProvider;

    /**
     * @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $tokenStorage;

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

    protected function setUp()
    {
        $this->categoryTreeProvider = $this->getMockBuilder(CategoryTreeProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->featuredCategoriesProvider = new FeaturedCategoriesProvider(
            $this->categoryTreeProvider,
            $this->tokenStorage,
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
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(new CustomerUser()));

        $categories = [];
        foreach ($data as $categoryData) {
            $categories[] = $this->getEntity(Category::class, $categoryData);
        }

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->categoryTreeProvider->expects($this->once())
            ->method('getCategories')
            ->with($this->tokenStorage->getToken()->getUser())
            ->willReturn($categories);

        $this->cache->expects($this->exactly(2))
            ->method('save');

        $actual = $this->featuredCategoriesProvider->getAll($categoryIds);
        $this->assertEquals($result, $actual);
    }

    public function testGetAllCached()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(new CustomerUser()));

        $result = ['id' => 1, 'title' => '', 'small_image' => null];

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('cacheVal_featured_categories__0_1')
            ->willReturn($result);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token));

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
                    ['id' => 1, 'title' => '', 'small_image' => null]
                ]
            ],
        ];
    }
}
