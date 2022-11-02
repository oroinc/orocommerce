<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ORM\Walker;

use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalkerHintProvider;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class PriceShardWalkerHintProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetHints()
    {
        /** @var ShardManager|\PHPUnit\Framework\MockObject\MockObject $shardManager */
        $shardManager = $this->createMock(ShardManager::class);
        $provider = new PriceShardWalkerHintProvider($shardManager);
        $this->assertEquals(
            [PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER => $shardManager],
            $provider->getHints([])
        );
    }
}
