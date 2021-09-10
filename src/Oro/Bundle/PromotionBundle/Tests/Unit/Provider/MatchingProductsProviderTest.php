<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;

class MatchingProductsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var SegmentManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $segmentManager;

    /**
     * @var MatchingProductsProvider
     */
    private $provider;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $matchingProductsCache;

    protected function setUp(): void
    {
        $this->segmentManager = $this->createMock(SegmentManager::class);
        $this->matchingProductsCache = $this->createMock(CacheProvider::class);
        $this->provider = new MatchingProductsProvider($this->segmentManager, $this->matchingProductsCache);
    }

    public function testHasMatchingProductsThrowsExceptionWhenNoRootAlias()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No root alias for segment\'s query builder');

        $segment = new Segment();
        $this->expectsQueryBuilderWithNoRootAlias($segment);

        $this->provider->hasMatchingProducts($segment, [new DiscountLineItem()]);
    }

    public function testGetMatchingProductsFromCache()
    {
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['definition' => 'some definition']);
        $product = $this->getEntity(Product::class, ['id' => 123]);

        $hash = md5('some definition_123');

        $this->matchingProductsCache->expects($this->once())
            ->method('fetch')
            ->with($hash)
            ->willReturn([
                $product
            ]);

        $this->segmentManager->expects($this->never())
            ->method('getSegmentQueryBuilder');

        $this->provider->getMatchingProducts($segment, [(new DiscountLineItem())->setProduct($product)]);
    }

    public function testGetMatchingProductsThrowsExceptionWhenNoRootAlias()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No root alias for segment\'s query builder');

        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['definition' => 'some definition']);
        $this->expectsQueryBuilderWithNoRootAlias($segment);

        $lineItemProduct = $this->getEntity(Product::class, ['id' => 123]);
        $hash = md5('some definition_123');

        $this->matchingProductsCache->expects($this->once())
            ->method('fetch')
            ->with($hash)
            ->willReturn(false);

        $this->provider->getMatchingProducts($segment, [(new DiscountLineItem())->setProduct($lineItemProduct)]);
    }

    public function testHasMatchingProductsThrowsExceptionWhenNoQueryBuilder()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get query builder for segment');

        $segment = new Segment();
        $this->expectsNoQueryBuilder($segment);

        $this->provider->hasMatchingProducts($segment, [new DiscountLineItem()]);
    }

    public function testGetMatchingProductsThrowsExceptionWhenNoQueryBuilder()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot get query builder for segment');

        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['definition' => 'some definition']);
        $this->expectsNoQueryBuilder($segment);

        $lineItemProduct = $this->getEntity(Product::class, ['id' => 123]);

        $hash = md5('some definition_123');
        $this->matchingProductsCache->expects($this->once())
            ->method('fetch')
            ->with($hash)
            ->willReturn(false);

        $this->provider->getMatchingProducts($segment, [(new DiscountLineItem())->setProduct($lineItemProduct)]);
    }

    public function testHasMatchingProductsWhenEmptyLineItems()
    {
        static::assertFalse($this->provider->hasMatchingProducts(new Segment(), []));
    }

    public function testGetMatchingProductsWhenEmptyLineItems()
    {
        static::assertEmpty($this->provider->getMatchingProducts(new Segment(), []));
    }

    private function expectsQueryBuilderWithNoRootAlias(Segment $segment)
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder
            ->expects($this->once())
            ->method('getRootAliases')
            ->willReturn([]);

        $this->segmentManager
            ->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn($queryBuilder);
    }

    private function expectsNoQueryBuilder(Segment $segment)
    {
        $this->segmentManager
            ->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn(null);
    }
}
