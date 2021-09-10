<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Layout\DataProvider\NewArrivalsProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
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

    public function testGetProductsWithInvalidCache(): void
    {
        $this->prepare();

        $this->getProductsWithInvalidCache($this->getQueryBuilder());
    }

    public function testGetProductsWithoutSegment()
    {
        $this->configManager->expects($this->atLeast(2))
            ->method('get')
            ->willReturnCallback(function ($name) {
                if ('oro_product.new_arrivals_products_segment_id' === $name) {
                    return 1;
                }

                return null;
            });

        $this->getProductsWithoutSegment();
    }

    public function testGetProductsQueryBuilderIsNull()
    {
        $this->prepare();

        $this->getProductsQueryBuilderIsNull();
    }

    protected function createSegmentProvider(ManagerRegistry $registry)
    {
        $this->segmentProductsProvider = new NewArrivalsProvider(
            $this->segmentManager,
            $this->websiteManager,
            $this->productSegmentProvider,
            $this->productManager,
            $this->configManager,
            $registry,
            $this->tokenStorage,
            $this->crypter,
            $this->aclHelper
        );
    }

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return 'new_arrivals_products_0_1__1';
    }

    private function prepare()
    {
        $this->configManager->expects($this->atLeast(2))
            ->method('get')
            ->willReturnCallback(function ($name) {
                if ('oro_product.new_arrivals_products_segment_id' === $name) {
                    return 1;
                }

                return null;
            });

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn(null);
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($this->getEntity(Website::class, ['id' => 1]));
        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
    }
}
