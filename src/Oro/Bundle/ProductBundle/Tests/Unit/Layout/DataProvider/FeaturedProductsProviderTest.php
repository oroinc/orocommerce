<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\QueryBuilder;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\ProductBundle\Layout\DataProvider\FeaturedProductsProvider;

class FeaturedProductsProviderTest extends AbstractSegmentProductsProviderTest
{
    public function testGetProducts()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->with('oro_product.featured_products_segment_id')
            ->willReturn(1);

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->segmentManager
            ->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->willReturn($queryBuilder);
        $this->productManager
            ->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, [])
            ->willReturn($queryBuilder);

        $this->getProducts($queryBuilder);
    }

    public function testGetProductsWithCache()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->with('oro_product.featured_products_segment_id')
            ->willReturn(1);

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->getProductsWithCache();
    }

    public function testGetProductsWithDisabledCache()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->with('oro_product.featured_products_segment_id')
            ->willReturn(1);

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->segmentManager
            ->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->willReturn($queryBuilder);
        $this->productManager
            ->expects($this->once())
            ->method('restrictQueryBuilder')
            ->with($queryBuilder, [])
            ->willReturn($queryBuilder);

        $this->segmentProductsProvider->disableCache();
        $this->getProductsWithDisabledCache($queryBuilder);
    }

    public function testGetProductsWithoutSegment()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->with('oro_product.featured_products_segment_id')
            ->willReturn(1);

        $this->getProductsWithoutSegment();
    }

    public function testGetProductsQueryBuilderIsNull()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->with('oro_product.featured_products_segment_id')
            ->willReturn(1);

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->getProductsQueryBuilderIsNull();
    }

    /**
     * @param RegistryInterface $registry
     */
    protected function createSegmentProvider(RegistryInterface $registry)
    {
        $this->segmentProductsProvider = new FeaturedProductsProvider(
            $this->segmentManager,
            $this->productSegmentProvider,
            $this->productManager,
            $this->configManager,
            $registry,
            $this->tokenStorage
        );
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return 'cacheVal_featured_products_0_';
    }
}
