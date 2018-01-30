<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Api\ProductPrice\PriceListIDContextStorageInterface;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\EnableShardingOnConfig;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use PHPUnit\Framework\TestCase;

class EnableShardingOnConfigTest extends TestCase
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
     * @var EnableShardingOnConfig
     */
    private $processor;

    protected function setUp()
    {
        $this->shardManager = $this->createMock(ShardManager::class);
        $this->priceListIDContextStorage = $this->createMock(PriceListIDContextStorageInterface::class);
        $this->context = $this->createMock(Context::class);

        $this->processor = new EnableShardingOnConfig(
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

    public function testProcessNoConfig()
    {
        $this->context
            ->expects(static::once())
            ->method('getQuery')
            ->willReturn($this->createMock(QueryBuilder::class));

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

        $config = $this->createMock(EntityDefinitionConfig::class);
        $config
            ->expects(static::exactly(3))
            ->method('addHint')
            ->withConsecutive(
                ['priceList', $priceListId],
                [PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager],
                [Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class]
            );

        $this->context
            ->expects(static::once())
            ->method('getQuery')
            ->willReturn($queryBuilder);
        $this->context
            ->expects(static::once())
            ->method('getConfig')
            ->willReturn($config);

        $this->processor->process($this->context);
    }
}
