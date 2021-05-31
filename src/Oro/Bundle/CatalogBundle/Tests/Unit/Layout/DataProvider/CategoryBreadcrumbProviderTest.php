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
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;

class CategoryBreadcrumbProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CategoryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryProvider;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    /** @var Router|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var Category|\PHPUnit\Framework\MockObject\MockObject */
    private $category;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var CategoryBreadcrumbProvider */
    private $categoryBreadcrumbProvider;

    protected function setUp(): void
    {
        $this->categoryProvider = $this->createMock(CategoryProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        $this->router = $this->createMock(Router::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->category = $this->createMock(Category::class);
        $categoryRepository = $this->createMock(CategoryRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(Category::class)
            ->willReturn($categoryRepository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Category::class)
            ->willReturn($manager);

        $this->categoryProvider->expects($this->once())
            ->method('getCurrentCategory')
            ->willReturn($this->category);

        $this->categoryBreadcrumbProvider = new CategoryBreadcrumbProvider(
            $this->categoryProvider,
            $this->localizationHelper,
            $this->router,
            $this->requestStack
        );
    }

    public function testGetItemsRoot()
    {
        $collection = $this->createMock(Collection::class);

        $this->router->expects($this->once())
            ->method('generate')
            ->willReturn('/');

        $this->category->expects($this->once())
            ->method('getTitles')
            ->willReturn($collection);

        $this->categoryProvider->expects($this->once())
            ->method('getParentCategories')
            ->willReturn([]);

        $this->localizationHelper->expects($this->once())
            ->method('getLocalizedValue')
            ->with($collection)
            ->willReturn('root');

        $result = $this->categoryBreadcrumbProvider->getItems();
        $breadcrumb = [
            'label' => 'root',
            'url'   => '/'
        ];
        $this->assertEquals([$breadcrumb], $result);
    }

    public function testGetItems()
    {
        $collection1 = new ArrayCollection();
        $collection2 = new ArrayCollection();

        $parentCategory = $this->createMock(Category::class);
        $parentCategory->expects($this->once())
            ->method('getTitles')
            ->willReturn($collection2);

        $this->category->expects($this->once())
            ->method('getTitles')
            ->willReturn($collection1);
        $this->category->expects($this->once())
            ->method('getId')
            ->willReturn(2);

        $this->localizationHelper->expects($this->exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive([$collection1], [$collection2])
            ->willReturnOnConsecutiveCalls('root', 'office');

        $this->categoryProvider->expects($this->once())
            ->method('getParentCategories')
            ->willReturn([$parentCategory]);

        $this->categoryProvider->expects($this->once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(1);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->withConsecutive(
                ['oro_product_frontend_product_index'],
                [
                    'oro_product_frontend_product_index',
                    [
                        'categoryId'           => 2,
                        'includeSubcategories' => 1
                    ]
                ]
            )
            ->willReturnOnConsecutiveCalls(
                '/',
                '/?c=2'
            );

        $result = $this->categoryBreadcrumbProvider->getItems();
        $breadcrumbs = [
            [
                'label' => 'root',
                'url'   => '/'
            ],
            [
                'label' => 'office',
                'url'   => '/?c=2'
            ]
        ];
        $this->assertEquals($breadcrumbs, $result);
    }

    public function testGetItemsForProduct()
    {
        $categoryId = 4;

        $collection1 = new ArrayCollection();
        $collection2 = new ArrayCollection();

        $parentCategory = $this->createMock(Category::class);
        $parentCategory->expects($this->once())
            ->method('getTitles')
            ->willReturn($collection2);

        $this->category->expects($this->once())
            ->method('getTitles')
            ->willReturn($collection1);
        $this->category->expects($this->once())
            ->method('getId')
            ->willReturn($categoryId);

        $this->localizationHelper->expects($this->exactly(2))
            ->method('getLocalizedValue')
            ->withConsecutive(
                [$collection1],
                [$collection2]
            )
            ->willReturnOnConsecutiveCalls('root', 'office');

        $this->categoryProvider->expects($this->once())
            ->method('getParentCategories')
            ->willReturn([$parentCategory]);
        $this->categoryProvider->expects($this->once())
            ->method('getIncludeSubcategoriesChoice')
            ->willReturn(1);

        $this->router->expects($this->exactly(2))
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
        $this->requestStack->expects($this->once())
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
        $this->assertEquals($expectedBreadcrumbs, $result);
    }
}
