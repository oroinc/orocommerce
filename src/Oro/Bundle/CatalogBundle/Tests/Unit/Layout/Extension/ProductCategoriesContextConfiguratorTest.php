<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Layout\Extension\ProductCategoriesContextConfigurator;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductCategoriesContextConfiguratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject */
    private $requestStack;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $currentRequest;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var CategoryProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $categoryProvider;

    /** @var ProductCategoriesContextConfigurator */
    private $configurator;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->categoryProvider = $this->createMock(CategoryProvider::class);

        $this->currentRequest = $this->createMock(Request::class);
        $this->currentRequest->attributes = $this->createMock(ParameterBag::class);

        $this->configurator = new ProductCategoriesContextConfigurator(
            $this->requestStack,
            $this->registry,
            $this->categoryProvider
        );
    }

    public function testConfigureForProductList()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->currentRequest);

        $this->currentRequest->attributes->expects($this->any())
            ->method('get')
            ->with('_route')
            ->willReturn(ProductCategoriesContextConfigurator::PRODUCT_LIST_ROUTE);

        $parentCategory = $this->getEntity(Category::class, ['id' => 1]);
        $category = $this->getEntity(Category::class, ['id' => 2, 'parentCategory' => $parentCategory]);

        $this->categoryProvider->expects($this->any())
            ->method('getCurrentCategory')
            ->willReturn($category);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->exactly(2))
            ->method('getResolver')
            ->willReturn($this->createMock(OptionsResolver::class));

        $context->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [ProductCategoriesContextConfigurator::CATEGORY_IDS_OPTION_NAME, [2, 1]],
                [ProductCategoriesContextConfigurator::CATEGORY_ID_OPTION_NAME, 2]
            );

        $this->configurator->configureContext($context);
    }

    public function testConfigureForProductView()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->currentRequest);

        $this->currentRequest->attributes->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($param) {
                switch ($param) {
                    case '_route':
                        return ProductCategoriesContextConfigurator::PRODUCT_VIEW_ROUTE;
                    case '_route_params':
                        return ['id' => 1];
                    default:
                        return null;
                }
            });

        $parentCategory = $this->getEntity(Category::class, ['id' => 1]);
        $category = $this->getEntity(Category::class, ['id' => 2, 'parentCategory' => $parentCategory]);

        $product = $this->getEntity(Product::class);
        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects($this->once())
            ->method('findOneByProduct')
            ->with($product)
            ->willReturn($category);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))
            ->method('getRepository')
            ->willReturnMap([
                [Product::class, $productRepository],
                [Category::class, $categoryRepository]
            ]);

        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->willReturn($em);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->exactly(2))
            ->method('getResolver')
            ->willReturn($this->createMock(OptionsResolver::class));

        $context->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [ProductCategoriesContextConfigurator::CATEGORY_IDS_OPTION_NAME, [2, 1]],
                [ProductCategoriesContextConfigurator::CATEGORY_ID_OPTION_NAME, 2]
            );

        $this->configurator->configureContext($context);
    }

    public function testConfigureForNotFoundProductView()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->currentRequest);

        $this->currentRequest->attributes->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($param) {
                switch ($param) {
                    case '_route':
                        return ProductCategoriesContextConfigurator::PRODUCT_VIEW_ROUTE;
                    case '_route_params':
                        return ['id' => 1];
                    default:
                        return null;
                }
            });

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects($this->never())
            ->method('findOneByProduct');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($productRepository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->never())
            ->method('getResolver');

        $context->expects($this->never())
            ->method('set');

        $this->configurator->configureContext($context);
    }

    public function testConfigureWhenCurrentRequestIsNotSet()
    {
        $requestStack = $this->createMock(RequestStack::class);
        $registry = $this->createMock(ManagerRegistry::class);
        $categoryProvider = $this->createMock(CategoryProvider::class);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->never())
            ->method('getResolver')
            ->willReturn($this->createMock(OptionsResolver::class));

        $configurator = new ProductCategoriesContextConfigurator($requestStack, $registry, $categoryProvider);
        $configurator->configureContext($context);
    }

    public function testConfigureWhenCurrentCategoryIsNotSet()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->currentRequest);

        $this->currentRequest->attributes->expects($this->any())
            ->method('get')
            ->with('_route')
            ->willReturn(ProductCategoriesContextConfigurator::PRODUCT_LIST_ROUTE);

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->exactly(2))
            ->method('getResolver')
            ->willReturn($this->createMock(OptionsResolver::class));

        $context->expects($this->exactly(2))
            ->method('set')
            ->withConsecutive(
                [ProductCategoriesContextConfigurator::CATEGORY_IDS_OPTION_NAME, []],
                [ProductCategoriesContextConfigurator::CATEGORY_ID_OPTION_NAME, $this->isNull()]
            );

        $this->configurator->configureContext($context);
    }

    public function testConfigureForNotAllowedRoute()
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($this->currentRequest);

        $this->currentRequest->attributes->expects($this->any())
            ->method('get')
            ->with('_route')
            ->willReturn('not_allowed_route');

        $context = $this->createMock(ContextInterface::class);
        $context->expects($this->never())
            ->method('getResolver');

        $this->configurator->configureContext($context);
    }
}
