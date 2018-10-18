<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\CreateProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\EnableShardingOnQuery;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class EnableShardingOnQueryTest extends CreateProcessorTestCase
{
    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var EnableShardingOnQuery */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->shardManager = $this->createMock(ShardManager::class);

        $this->processor = new EnableShardingOnQuery($this->shardManager);
    }

    public function testProcessWrongQuery()
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasQuery());
    }

    public function testProcess()
    {
        $priceListId = 1;

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::exactly(3))
            ->method('setHint')
            ->withConsecutive(
                ['priceList', $priceListId],
                [PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager],
                [Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class]
            );

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('e.priceList = :price_list_id')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('price_list_id', $priceListId);
        $queryBuilder->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);

        $this->context->set('price_list_id', $priceListId);
        $this->context->setQuery($queryBuilder);
        $this->processor->process($this->context);
        self::assertSame($query, $this->context->getQuery());
    }
}
