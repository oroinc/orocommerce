<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\ProductPrice\Processor;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\ProductPrice\Processor\EnableShardingOnConfig;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class EnableShardingOnConfigTest extends GetListProcessorTestCase
{
    /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shardManager;

    /** @var EnableShardingOnConfig */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->shardManager = $this->createMock(ShardManager::class);

        $this->processor = new EnableShardingOnConfig($this->shardManager);
    }

    public function testProcessWrongQuery()
    {
        $this->processor->process($this->context);
    }

    public function testProcessNoConfig()
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasQuery());
    }

    public function testProcess()
    {
        $priceListId = 1;

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('andWhere')
            ->with('e.priceList = :price_list_id')
            ->willReturn($queryBuilder);
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('price_list_id', $priceListId);

        $config = $this->createMock(EntityDefinitionConfig::class);
        $config->expects(self::exactly(3))
            ->method('addHint')
            ->withConsecutive(
                ['priceList', $priceListId],
                [PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager],
                [Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class]
            );

        $this->context->set('price_list_id', $priceListId);
        $this->context->setQuery($queryBuilder);
        $this->context->setConfig($config);
        $this->processor->process($this->context);
    }
}
