<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\DTO\Category as CategoryDTO;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class CategoryProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

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
     * @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenAccessor;

    /**
     * @var MasterCatalogRootProviderInterface
     */
    private $masterCatalogProvider;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var CategoryProvider
     */
    protected $categoryProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->requestProductHandler = $this->createMock(RequestProductHandler::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->categoryTreeProvider = $this->createMock(CategoryTreeProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->masterCatalogProvider = $this->createMock(MasterCatalogRootProviderInterface::class);
        $this->cache = $this->createMock(CacheProvider::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($this->categoryRepository);

        /** @var ManagerRegistry $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->categoryProvider = new CategoryProvider(
            $this->requestProductHandler,
            $registry,
            $this->categoryTreeProvider,
            $this->tokenAccessor,
            $this->localizationHelper,
            $this->masterCatalogProvider
        );
        $this->categoryProvider->setCache($this->cache, 3600);
    }

    public function testGetCurrentCategoryUsingMasterCatalogRoot()
    {
        $category = new Category();

        $this->requestProductHandler
            ->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(0);

        $this->masterCatalogProvider
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($category);

        $result = $this->categoryProvider->getCurrentCategory();
        $this->assertSame($category, $result);
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
        $category = new Category();

        $this->masterCatalogProvider
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

        $this->masterCatalogProvider
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
        $childCategory->addTitle((new CategoryTitle())->setString('category_1_2_3'));

        $mainCategory = new Category();
        $mainCategory->setLevel(1);
        $mainCategory->setMaterializedPath('1_2');
        $mainCategory->addTitle((new CategoryTitle())->setString('category_1_2'));
        $mainCategory->addChildCategory($childCategory);

        $rootCategory = new Category();
        $rootCategory->setLevel(0);
        $rootCategory->setMaterializedPath('1');
        $rootCategory->addChildCategory($mainCategory);

        $user = new CustomerUser();

        $this->masterCatalogProvider
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($rootCategory);

        $this->categoryTreeProvider->expects($this->once())
            ->method('getCategories')
            ->with($user, $rootCategory, null)
            ->willReturn([$mainCategory, $childCategory]);

        $this->localizationHelper
            ->expects($this->any())
            ->method('getLocalizedValue')
            ->willReturnCallback(
                function (ArrayCollection $values) {
                    return $values->first();
                }
            );

        return $user;
    }

    public function testGetCategoryTreeArray()
    {
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

        $organization = new Organization();
        $this->tokenAccessor
            ->expects($this->exactly(1))
            ->method('getOrganization')
            ->willReturn($organization);

        $user = $this->prepareGetCategoryTreeArray();
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('category__0_0_0_')
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('category__0_0_0_', $data)
            ->willReturn(false);

        $actual = $this->categoryProvider->getCategoryTreeArray($user);
        $this->assertEquals($data, $actual);
    }

    public function testGetCategoryTreeArrayCached()
    {
        $data = [
            [
                'id' => '',
                'title' => 'category_1_2',
                'hasSublist' => 1,
                'childCategories' => []
            ]
        ];
        $user = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $customer = $this->getEntity(Customer::class, ['id' => 2]);
        $user->setCustomer($customer);
        $customerGroup = $this->getEntity(CustomerGroup::class, ['id' => 3]);
        $customer->setGroup($customerGroup);
        $organization = $this->getEntity(CustomerUser::class, ['id' => 4]);
        $localization = $this->getEntity(Localization::class, ['id' => 5]);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganization')
            ->willReturn($organization);
        $this->localizationHelper->expects($this->any())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->with('category_1_5_2_3_4')
            ->willReturn($data);
        $this->cache->expects($this->never())
            ->method('save');

        $actual = $this->categoryProvider->getCategoryTreeArray($user);
        $this->assertEquals($data, $actual);
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
