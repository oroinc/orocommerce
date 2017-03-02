<?php

namespace Oro\Bundle\PricingBundle\Tests\FunctionalSharding;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Unit\EntityTrait;

class ShardManagerTest extends WebTestCase
{
    use EntityTrait;

    /**
     * @var ShardManager
     */
    private $manager;

    public function setUp()
    {
        parent::setUp();
        $this->initClient();
        $this->manager = $this->getContainer()->get('oro_pricing.shard_manager');
    }

    public function testGetShardName()
    {
        $priceList = $this->getEntity(PriceList::class, ['id' => 1]);
        $actual = $this->manager->getShardName(ProductPrice::class, ['priceList' => $priceList]);
        $this->assertSame('oro_price_product_1', $actual);
    }

    public function testGetShardNameEx()
    {
        // todo: test invalid class end attributes
    }

    public function testCreateAndDeleteNewShard()
    {
        $shardName = 'oro_price_product_1';

        $this->assertFalse($this->manager->exists(ProductPrice::class, $shardName));

        $this->manager->create(ProductPrice::class, $shardName);
        $this->assertTrue($this->manager->exists(ProductPrice::class, $shardName));

        $this->manager->delete(ProductPrice::class, $shardName);
        $this->assertFalse($this->manager->exists(ProductPrice::class, $shardName));
    }
}
