<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ORM\Walker;

use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalkerHintProvider;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class PriceShardWalkerHintProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetHints()
    {
        /** @var ShardManager|\PHPUnit_Framework_MockObject_MockObject $shardManager */
        $shardManager = $this->createMock(ShardManager::class);
        $provider = new PriceShardWalkerHintProvider($shardManager);
        $this->assertEquals([PriceShardWalker::ORO_PRICING_SHARD_MANAGER => $shardManager], $provider->getHints([]));
    }
}
