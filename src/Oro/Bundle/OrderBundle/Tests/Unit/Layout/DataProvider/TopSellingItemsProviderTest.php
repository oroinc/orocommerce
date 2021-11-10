<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\Layout\DataProvider\TopSellingItemsProvider;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\ReflectionUtil;

class TopSellingItemsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var ProductManager|\PHPUnit\Framework\MockObject\MockObject */
    private $productManager;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var ProductListBuilder|\PHPUnit\Framework\MockObject\MockObject */
    private $productListBuilder;

    /** @var TopSellingItemsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->productManager = $this->createMock(ProductManager::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->productListBuilder = $this->createMock(ProductListBuilder::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->productRepository);

        $this->provider = new TopSellingItemsProvider(
            $doctrine,
            $this->productManager,
            $this->aclHelper,
            $this->productListBuilder
        );
    }

    private function getProduct(int $id): Product
    {
        $product = new Product();
        ReflectionUtil::setId($product, $id);

        return $product;
    }

    private function getProductView(int $id): ProductView
    {
        $productView = new ProductView();
        $productView->set('id', $id);

        return $productView;
    }

    public function testGetProductsWhenNoTopSellingItems()
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('product.id')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([]);

        $this->productRepository->expects(self::once())
            ->method('getFeaturedProductsQueryBuilder')
            ->with(10)
            ->willReturn($queryBuilder);

        $this->productManager->expects(self::once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, []);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->productListBuilder->expects(self::never())
            ->method('getProductsByIds');

        self::assertSame([], $this->provider->getProducts());
        // test memory cache
        self::assertSame([], $this->provider->getProducts());
    }

    public function testGetProducts()
    {
        $product = $this->getProduct(1);
        $expectedProducts = [$this->getProductView($product->getId())];

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('select')
            ->with('product.id')
            ->willReturnSelf();

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getArrayResult')
            ->willReturn([['id' => $product->getId()]]);

        $this->productRepository->expects(self::once())
            ->method('getFeaturedProductsQueryBuilder')
            ->with(10)
            ->willReturn($queryBuilder);

        $this->productManager->expects(self::once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, []);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $this->productListBuilder->expects(self::once())
            ->method('getProductsByIds')
            ->with('top_selling_items', [$product->getId()])
            ->willReturn($expectedProducts);

        self::assertEquals($expectedProducts, $this->provider->getProducts());
        // test memory cache
        self::assertEquals($expectedProducts, $this->provider->getProducts());
    }
}
