<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;

class MatchingProductsProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SegmentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $segmentManager;

    /**
     * @var MatchingProductsProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->segmentManager = $this->getMockBuilder(SegmentManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new MatchingProductsProvider($this->segmentManager);
    }

    public function testHasMatchingProductsThrowsExceptionWhenNoRootAlias()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No root alias for segment\'s query builder');

        $segment = new Segment();
        $this->expectsQueryBuilderWithNoRootAlias($segment);

        $this->provider->hasMatchingProducts($segment, [new DiscountLineItem()]);
    }

    public function testGetMatchingProductsThrowsExceptionWhenNoRootAlias()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No root alias for segment\'s query builder');

        $segment = new Segment();
        $this->expectsQueryBuilderWithNoRootAlias($segment);

        $this->provider->getMatchingProducts($segment, [new DiscountLineItem()]);
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

        $segment = new Segment();
        $this->expectsNoQueryBuilder($segment);

        $this->provider->getMatchingProducts($segment, [new DiscountLineItem()]);
    }

    public function testHasMatchingProductsWhenEmptyLineItems()
    {
        static::assertFalse($this->provider->hasMatchingProducts(new Segment(), []));
    }

    public function testGetMatchingProductsWhenEmptyLineItems()
    {
        static::assertEmpty($this->provider->getMatchingProducts(new Segment(), []));
    }

    /**
     * @param Segment $segment
     */
    private function expectsQueryBuilderWithNoRootAlias(Segment $segment)
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

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

    /**
     * @param Segment $segment
     */
    private function expectsNoQueryBuilder(Segment $segment)
    {
        $this->segmentManager
            ->expects($this->once())
            ->method('getEntityQueryBuilder')
            ->with($segment)
            ->willReturn(null);
    }
}
