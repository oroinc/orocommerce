<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\OrderBundle\Layout\DataProvider\TopSellingItemsProvider;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class TopSellingItemsProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetAllWithDefaultQuantity()
    {
        $products = [new Product()];

        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getResult')
            ->willReturn($products);
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->expects($this->once())
            ->method('getFeaturedProductsQueryBuilder')
            ->with(TopSellingItemsProvider::DEFAULT_QUANTITY)
            ->willReturn($queryBuilder);

        $productManager = $this->createMock(ProductManager::class);
        $productManager->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, []);

        $aclHelper = $this->createMock(AclHelper::class);
        $aclHelper->expects($this->once())
            ->method('apply')
            ->with($queryBuilder)
            ->willReturn($query);

        $provider = new TopSellingItemsProvider($productRepository, $productManager, $aclHelper);
        $this->assertEquals($products, $provider->getProducts());
    }
}
