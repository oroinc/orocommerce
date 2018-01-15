<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\EnableShardingOnQuery;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use PHPUnit\Framework\TestCase;

class EnableShardingOnQueryTest extends TestCase
{
    /**
     * @var ShardManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shardManager;

    /**
     * @var PriceListIDContextStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceListIDContextStorage;

    /**
     * @var Context|\PHPUnit_Framework_MockObject_MockObject
     */
    private $context;

    /**
     * @var EnableShardingOnQuery
     */
    private $processor;

    protected function setUp()
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->priceListIDContextStorage = $this->createMock(PriceListIDContextStorageInterface::class);
        $this->context = $this->createMock(Context::class);

        $this->processor = new EnableShardingOnQuery(
            $this->shardManager,
            $this->priceListIDContextStorage
        );
    }

    public function testProcessWrongType()
    {
        $this->priceListIDContextStorage
            ->expects(static::never())
            ->method('get');

        $this->processor->process($this->createMock(ApiContext::class));
    }

    public function testProcessWrongQuery()
    {
        $this->priceListIDContextStorage
            ->expects(static::never())
            ->method('get');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $priceListId = 1;

        $this->priceListIDContextStorage
            ->expects(static::once())
            ->method('get')
            ->willReturn($priceListId);

        $query = $this->createMock(AbstractQuery::class);
        $query
            ->expects(static::exactly(3))
            ->method('setHint')
            ->withConsecutive(
                ['priceList', $priceListId],
                [PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager],
                [Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class]
            );

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(static::once())
            ->method('andWhere')
            ->with('e.priceList = :price_list_id')
            ->willReturn($queryBuilder);
        $queryBuilder
            ->expects(static::once())
            ->method('setParameter')
            ->with('price_list_id', $priceListId);
        $queryBuilder
            ->expects(static::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->context
            ->expects(static::once())
            ->method('getQuery')
            ->willReturn($queryBuilder);
        $this->context
            ->expects(static::once())
            ->method('setQuery')
            ->with($query);

        $this->processor->process($this->context);
    }
}
