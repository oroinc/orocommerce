<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Layout\DataProvider\FeaturedProductsProvider;

class FeaturedProductsProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetAllWithDefaultQuantity()
    {
        $queryBuilder      = $this->createQueryBuilder();
        $productRepository = $this->createProductRepository();
        $productRepository->expects($this->once())
            ->method('getFeaturedProductsQueryBuilder')
            ->with(FeaturedProductsProvider::DEFAULT_QUANTITY)
            ->will($this->returnValue($queryBuilder));
        $productManager = $this->createProductManager();
        $productManager->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, []);
        $this->createFeaturedProductsProvider($productRepository, $productManager)->getAll();
    }

    public function testGetAllWithQuantity()
    {
        $quantity          = 15;
        $queryBuilder      = $this->createQueryBuilder();
        $productRepository = $this->createProductRepository();
        $productRepository->expects($this->once())
            ->method('getFeaturedProductsQueryBuilder')
            ->with($quantity)
            ->will($this->returnValue($queryBuilder));
        $this->createFeaturedProductsProvider($productRepository, $this->createProductManager())->getAll($quantity);
    }

    /**
     * @param ProductRepository|\PHPUnit_Framework_MockObject_MockObject $productRepository
     * @param ProductManager|\PHPUnit_Framework_MockObject_MockObject    $productManager
     *
     * @return FeaturedProductsProvider
     */
    protected function createFeaturedProductsProvider($productRepository, $productManager)
    {
        return new FeaturedProductsProvider($productRepository, $productManager);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProductRepository()
    {
        return $this->createMock(ProductRepository::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProductManager()
    {
        return $this->createMock(ProductManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createQueryBuilder()
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($this->createQuery());

        return $queryBuilder;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createQuery()
    {
        return $this->createMock(AbstractQuery::class);
    }
}
