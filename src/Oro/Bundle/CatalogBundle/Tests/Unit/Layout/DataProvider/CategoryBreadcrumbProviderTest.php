<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryBreadcrumbProvider;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CategoryBreadcrumbProviderTest extends \PHPUnit\Framework\TestCase
{
    private CategoryProvider|\PHPUnit\Framework\MockObject\MockObject $categoryProvider;

    private LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper;

    private UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject $urlGenerator;

    private Category|\PHPUnit\Framework\MockObject\MockObject $category;

    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    private CategoryBreadcrumbProvider $categoryBreadcrumbProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->categoryProvider = $this->createMock(CategoryProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->category = $this->createMock(Category::class);
        $categoryRepository = $this->createMock(CategoryRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($categoryRepository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->categoryBreadcrumbProvider = new CategoryBreadcrumbProvider(
            $this->categoryProvider,
            $this->localizationHelper,
            $this->urlGenerator,
            $this->requestStack
        );

        $this->configManager = $this->createMock(ConfigManager::class);
        $this->categoryBreadcrumbProvider->setConfigManager($this->configManager);
    }

    public function testGetItemsRoot(): void
    {
        $collection = $this->createMock(Collection::class);

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->willReturn('/');

        $this->category->expects(self::once())
            ->method('getTitles')
            ->willReturn($collection);

        $this->categoryProvider->expects(self::once())
            ->method('getCategoryPath')
            ->willReturn([$this->category]);

        $this->localizationHelper->expects(self::once())
            ->method('getLocalizedValue')
            ->with($collection)
            ->willReturn('root');

        $result = $this->categoryBreadcrumbProvider->getItems();

        $expectedBreadcrumbs = [
            [
                'label' => 'root',
                'url'   => '/',
            ],
        ];
        self::assertEquals($expectedBreadcrumbs, $result);
    }

    public function testGetItems(): void
    {
        $categoryId = 2;

        $collection1 = new ArrayCollection();
        $collection2 = new ArrayCollection();

        $parentCategoryA = $this->createMock(Category::class);
        $parentCategoryA->expects(self::once())
            ->method('getTitles')
            ->willReturn($collection2);

        $this->category->expects(self::once())
            ->method('getTitles')
            ->willReturn($collection1);
        $this->category->expects(self::once())
            ->method('getId')
            ->willReturn($categoryId);

        $this->localizationHelper->expects(self::exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive([$collection1], [$collection2])
            ->willReturnOnConsecutiveCalls('root', 'office');

        $this->categoryProvider->expects(self::once())
            ->method('getCategoryPath')
            ->willReturn([$parentCategoryA, $this->category]);
        $this->categoryProvider->expects(self::once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(true);

        $this->urlGenerator->expects(self::exactly(2))
            ->method('generate')
            ->withConsecutive(
                ['oro_product_frontend_product_index'],
                [
                    'oro_product_frontend_product_index',
                    [
                        'categoryId'           => $categoryId,
                        'includeSubcategories' => 1
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                '/',
                '/?c=2'
            );

        $result = $this->categoryBreadcrumbProvider->getItems();

        $expectedBreadcrumbs = [
            [
                'label' => 'root',
                'url'   => '/',
            ],
            [
                'label' => 'office',
                'url'   => '/?c=2',
            ],
        ];
        self::assertEquals($expectedBreadcrumbs, $result);
    }

    public function testGetItemsForProduct(): void
    {
        $categoryId = 4;

        $collection1 = new ArrayCollection();
        $collection2 = new ArrayCollection();

        $parentCategoryA = $this->createMock(Category::class);
        $parentCategoryA->expects(self::once())
            ->method('getTitles')
            ->willReturn($collection2);

        $this->category->expects(self::once())
            ->method('getTitles')
            ->willReturn($collection1);
        $this->category->expects(self::once())
            ->method('getId')
            ->willReturn($categoryId);

        $this->localizationHelper->expects(self::exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive(
                [$collection1],
                [$collection2]
            )
            ->willReturnOnConsecutiveCalls('root', 'office');

        $this->categoryProvider->expects(self::once())
            ->method('getCategoryPath')
            ->willReturn([$parentCategoryA, $this->category]);
        $this->categoryProvider->expects(self::once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(true);

        $this->urlGenerator->expects(self::exactly(2))
            ->method('generate')
            ->withConsecutive(
                ['oro_product_frontend_product_index'],
                [
                    'oro_product_frontend_product_index',
                    [
                        'categoryId'           => $categoryId,
                        'includeSubcategories' => 1
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls('/', '/?c=2');

        $currentRequest = Request::create('/', Request::METHOD_GET);
        $currentRequest->attributes = new ParameterBag();
        $this->requestStack->expects(self::once())
            ->method('getCurrentRequest')
            ->willReturn($currentRequest);

        $currentPageTitle = '220 Lumen Rechargeable Headlamp';
        $result = $this->categoryBreadcrumbProvider->getItemsForProduct($categoryId, $currentPageTitle);

        $expectedBreadcrumbs = [
            [
                'label' => 'root',
                'url'   => '/'
            ],
            [
                'label' => 'office',
                'url'   => '/?c=2'
            ],
            [
                'label' => $currentPageTitle,
                'url'   => null
            ]
        ];
        self::assertEquals($expectedBreadcrumbs, $result);
    }

    public function testGetItemsForProductListingTrimsLastBreadcrumbWhenConfigEnabled(): void
    {
        $category = $this->createMock(Category::class);
        $collection = new ArrayCollection();

        $category->expects(self::any())->method('getTitles')->willReturn($collection);
        $category->expects(self::any())->method('getId')->willReturn(42);

        $this->categoryProvider->method('getCategoryPath')->willReturn([$category, $category]);
        $this->categoryProvider->method('getIncludeSubcategoriesChoice')->willReturn(true);
        $this->urlGenerator->method('generate')->willReturn('/');
        $this->localizationHelper->method('getLocalizedValue')->willReturn('Test');

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['oro_catalog.breadcrumbs_exclude_current_on_all_pages', false, false, null, true],
                ['oro_catalog.breadcrumbs_remove_single_breadcrumb', false, false, null, false],
            ]);

        $breadcrumbs = $this->categoryBreadcrumbProvider->getItems();
        self::assertCount(1, $breadcrumbs);
    }

    public function testGetItemsForProductTrimsLastBreadcrumbWhenConfigEnabled(): void
    {
        $category = $this->createMock(Category::class);
        $collection = new ArrayCollection();

        $category->expects(self::any())->method('getTitles')->willReturn($collection);
        $category->expects(self::any())->method('getId')->willReturn(42);

        $this->categoryProvider->method('getCategoryPath')->willReturn([$category, $category]);
        $this->categoryProvider->method('getIncludeSubcategoriesChoice')->willReturn(true);
        $this->urlGenerator->method('generate')->willReturn('/');
        $this->localizationHelper->method('getLocalizedValue')->willReturn('Test');

        $request = new Request();
        $request->attributes = new ParameterBag();
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['oro_product.breadcrumbs_exclude_current_on_product_view', false, false, null, true],
                ['oro_catalog.breadcrumbs_remove_single_breadcrumb', false, false, null, false],
            ]);

        $breadcrumbs = $this->categoryBreadcrumbProvider->getItemsForProduct(42, 'Product');
        self::assertCount(2, $breadcrumbs);
    }

    public function testGetItemsForProductListingRemovesSingleItemWhenConfigEnabled(): void
    {
        $category = $this->createMock(Category::class);
        $collection = new ArrayCollection();

        $category->expects(self::any())->method('getTitles')->willReturn($collection);

        $this->categoryProvider->method('getCategoryPath')->willReturn([$category]);
        $this->urlGenerator->method('generate')->willReturn('/');
        $this->localizationHelper->method('getLocalizedValue')->willReturn('Test');

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['oro_catalog.breadcrumbs_exclude_current_on_all_pages', false, false, null, false],
                ['oro_catalog.breadcrumbs_remove_single_breadcrumb', false, false, null, true],
            ]);

        $breadcrumbs = $this->categoryBreadcrumbProvider->getItems();
        self::assertSame([], $breadcrumbs);
    }

    public function testGetItemsForProductRemovesSingleItemWhenConfigEnabled(): void
    {
        $request = new Request();
        $request->attributes = new ParameterBag();
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->categoryProvider->method('getCategoryPath')->willReturn([]);
        $this->urlGenerator->method('generate')->willReturn('/');
        $this->localizationHelper->method('getLocalizedValue')->willReturn('Test');

        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturnMap([
                ['oro_product.breadcrumbs_exclude_current_on_product_view', false, false, null, false],
                ['oro_catalog.breadcrumbs_remove_single_breadcrumb', false, false, null, true],
            ]);

        $breadcrumbs = $this->categoryBreadcrumbProvider->getItemsForProduct(null, 'Title');
        self::assertSame([], $breadcrumbs);
    }
}
