<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Sharding;

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

    /**
     * @dataProvider getShardNameDataProvider
     * @param array $attributes
     */
    public function testGetShardName(array $attributes)
    {
        $actual = $this->manager->getShardName(ProductPrice::class, $attributes);
        $this->assertSame('oro_price_product_1', $actual);
    }

    /**
     * @return array
     */
    public function getShardNameDataProvider()
    {
        return [
            'object' => [
                'attributes' => ['priceList' => $this->getEntity(PriceList::class, ['id' => 1])],
            ],
            'id' => [
                'attributes' => ['priceList' => 1],
            ],
        ];
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Required attribute 'priceList' for generation of shard name missing.
     */
    public function testGetShardNameExceptionWhenParamMissing()
    {
        $this->manager->getShardName(ProductPrice::class, []);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Wrong type of 'priceList' to generate shard name.
     */
    public function testGetShardNameExceptionWhenParamNotValid()
    {
        $this->manager->getShardName(ProductPrice::class, ['priceList' => new \stdClass()]);
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

    public function testSerialization()
    {
        $this->manager->addEntityForShard(ProductPrice::class);
        $result = serialize($this->manager);
        /** @var ShardManager $newManager */
        $newManager = unserialize($result);
        $this->assertEquals($this->manager->getShardMap(), $newManager->getShardMap());
    }
}
