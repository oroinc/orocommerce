<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\DTO\Category as CategoryDTO;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

class CategoryProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RequestProductHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $requestProductHandler;

    /**
     * @var CategoryRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $categoryRepository;

    /**
     * @var CategoryTreeProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $categoryTreeProvider;

    /**
     * @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $localizationHelper;

    /**
     * @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteManager;

    /**
     * @var CategoryProvider
     */
    protected $categoryProvider;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->requestProductHandler = $this->createMock(RequestProductHandler::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->categoryTreeProvider = $this->createMock(CategoryTreeProvider::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->categoryProvider = new CategoryProvider(
            $this->requestProductHandler,
            $this->categoryRepository,
            $this->categoryTreeProvider,
            $this->websiteManager
        );
    }

    public function testGetCurrentCategoryUsingMasterCatalogRoot()
    {
        $organization = new Organization();
        $website = new Website();
        $website->setOrganization($organization);
        $category = new Category();

        $this->requestProductHandler
            ->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(null);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->categoryRepository
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->with($organization)
            ->willReturn($category);

        $result = $this->categoryProvider->getCurrentCategory();
        $this->assertSame($category, $result);
    }

    /**
     * @dataProvider getEmptyCurrentCateDataProvider
     *
     * @param Website $website
     */
    public function testGetCurrentCategoryNull(Website $website = null)
    {
        $this->requestProductHandler
            ->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(null);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->categoryRepository
            ->expects($this->never())
            ->method('getMasterCatalogRoot');

        $result = $this->categoryProvider->getCurrentCategory();
        $this->assertNull($result);
    }

    /**
     * @return array
     */
    public function getEmptyCurrentCateDataProvider()
    {
        return [
            'without website' => [
                'website' => null
            ],
            'without organization' => [
                'website' => new Website(),
            ]
        ];
    }

    public function testGetCurrentCategoryUsingFind()
    {
        $category = new Category();
        $categoryId = 1;

        $this->requestProductHandler
            ->expects($this->once())
            ->method('getCategoryId')
            ->willReturn($categoryId);

        $this->categoryRepository
            ->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $result = $this->categoryProvider->getCurrentCategory();
        $this->assertSame($category, $result);
    }

    public function testGetRootCategory()
    {
        $organization = new Organization();
        $website = new Website();
        $website->setOrganization($organization);
        $category = new Category();

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->categoryRepository
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($category);

        $result = $this->categoryProvider->getRootCategory();
        $this->assertSame($category, $result);
    }

    public function testGetCategoryTree()
    {
        $filteredChildCategory = new Category();
        $filteredChildCategory->setLevel(2);
        $filteredChildCategory->setMaterializedPath('1_2_4');

        $childCategory = new Category();
        $childCategory->setLevel(2);
        $childCategory->setMaterializedPath('1_2_3');

        $mainCategory = new Category();
        $mainCategory->setLevel(1);
        $mainCategory->setMaterializedPath('1_2');
        $mainCategory->addChildCategory($childCategory);
        $mainCategory->addChildCategory($filteredChildCategory);

        $rootCategory = new Category();
        $rootCategory->setLevel(0);
        $rootCategory->setMaterializedPath('1');
        $rootCategory->addChildCategory($mainCategory);

        $user = new CustomerUser();
        $organization = new Organization();
        $website = new Website();
        $website->setOrganization($organization);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->categoryRepository
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($rootCategory);

        $this->categoryTreeProvider->expects($this->once())
            ->method('getCategories')
            ->with($user, $rootCategory, null)
            ->willReturn([$mainCategory, $childCategory]);

        $actual = $this->categoryProvider->getCategoryTree($user);

        $expectedDTO = new CategoryDTO($mainCategory);
        $expectedDTO->addChildCategory(new CategoryDTO($childCategory));

        $this->assertEquals(new ArrayCollection([$expectedDTO]), $actual);
    }

    /**
     * @return CustomerUser
     */
    private function prepareGetCategoryTreeArray()
    {
        $childCategory = new Category();
        $childCategory->setLevel(2);
        $childCategory->setMaterializedPath('1_2_3');
        $childCategory->addTitle((new LocalizedFallbackValue())->setString('category_1_2_3'));

        $mainCategory = new Category();
        $mainCategory->setLevel(1);
        $mainCategory->setMaterializedPath('1_2');
        $mainCategory->addTitle((new LocalizedFallbackValue())->setString('category_1_2'));
        $mainCategory->addChildCategory($childCategory);

        $rootCategory = new Category();
        $rootCategory->setLevel(0);
        $rootCategory->setMaterializedPath('1');
        $rootCategory->addChildCategory($mainCategory);

        $user = new CustomerUser();
        $organization = new Organization();
        $website = new Website();
        $website->setOrganization($organization);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $this->categoryRepository
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($rootCategory);

        $this->categoryTreeProvider->expects($this->once())
            ->method('getCategories')
            ->with($user, $rootCategory, null)
            ->willReturn([$mainCategory, $childCategory]);

        $this->localizationHelper = $this->getMockBuilder(LocalizationHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localizationHelper
            ->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(
                function (ArrayCollection $values) {
                    return $values->first();
                }
            );

        $this->categoryProvider->setLocalizationHelper($this->localizationHelper);

        return $user;
    }

    public function testGetCategoryTreeArray()
    {
        $user = $this->prepareGetCategoryTreeArray();
        $this->categoryProvider->setCache(null);
        $actual = $this->categoryProvider->getCategoryTreeArray($user);

        $data = [
            [
                'id' => '',
                'title' => 'category_1_2',
                'hasSublist' => 1,
                'childCategories' => [
                [
                        'id' => '',
                        'title' => 'category_1_2_3',
                        'hasSublist' => 0,
                        'childCategories' => []
                    ]
                ]
            ]
        ];

        $this->assertEquals($data, $actual);
    }

    public function testGetCategoryTreeArrayNotCaching()
    {
        $user = $this->prepareGetCategoryTreeArray();

        $cache = $this->createMock(CacheProvider::class);
        $cache->expects($this->never())
            ->method('fetch');

        $this->categoryProvider->setCache($cache);
        $this->categoryProvider->disableCache();
        $this->categoryProvider->getCategoryTreeArray($user);
    }

    public function testGetCategoryTreeArrayCaching()
    {
        $user = $this->prepareGetCategoryTreeArray();

        $cache = $this->createMock(CacheProvider::class);
        $cache->expects($this->atLeastOnce())
            ->method('fetch');

        $this->categoryProvider->setCache($cache, 3600);
        $this->categoryProvider->getCategoryTreeArray($user);
    }

    public function testGetIncludeSubcategoriesChoice()
    {
        $this->requestProductHandler
            ->method('getIncludeSubcategoriesChoice')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertEquals(true, $this->categoryProvider->getIncludeSubcategoriesChoice());
        $this->assertEquals(false, $this->categoryProvider->getIncludeSubcategoriesChoice());
    }
}
