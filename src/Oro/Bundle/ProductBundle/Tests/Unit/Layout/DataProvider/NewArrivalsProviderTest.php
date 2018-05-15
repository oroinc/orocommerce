<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Layout\DataProvider\NewArrivalsProvider;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class NewArrivalsProviderTest extends AbstractSegmentProductsProviderTest
{
    public function testGetProducts()
    {
        $this->prepare();

        $this->getProducts($this->getQueryBuilder());
    }

    public function testGetProductsWithCache()
    {
        $this->prepare();

        $this->getProductsWithCache();
    }

    public function testGetProductsWithDisabledCache()
    {
        $this->prepare();

        $this->segmentProductsProvider->disableCache();
        $this->getProductsWithDisabledCache($this->getQueryBuilder());
    }

    public function testGetProductsWithoutSegment()
    {
        $this->configManager->expects($this->at(2))
            ->method('get')
            ->with('oro_product.new_arrivals_products_segment_id')
            ->willReturn(1);

        $this->configManager->expects($this->at(3))
            ->method('get')
            ->with('oro_product.new_arrivals_products_segment_id')
            ->willReturn(1);

        $this->getProductsWithoutSegment();
    }

    public function testGetProductsQueryBuilderIsNull()
    {
        $this->prepare();

        $this->getProductsQueryBuilderIsNull();
    }

    /**
     * @param RegistryInterface $registry
     */
    protected function createSegmentProvider(RegistryInterface $registry)
    {
        $this->segmentProductsProvider = new NewArrivalsProvider(
            $this->segmentManager,
            $this->productSegmentProvider,
            $this->productManager,
            $this->configManager,
            $registry,
            $this->tokenStorage,
            $this->crypter
        );
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return 'cacheVal_new_arrivals_products_0_';
    }

    private function prepare()
    {
        $this->configManager->expects($this->at(2))
            ->method('get')
            ->with('oro_product.new_arrivals_products_segment_id')
            ->willReturn(1);
        $this->configManager->expects($this->at(3))
            ->method('get')
            ->with('oro_product.new_arrivals_products_segment_id')
            ->willReturn(1);

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
    }
}
