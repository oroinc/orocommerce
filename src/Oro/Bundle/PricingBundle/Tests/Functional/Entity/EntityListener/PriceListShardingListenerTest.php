<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PriceListShardingListenerTest extends WebTestCase
{
    /** @var ShardManager */
    private $shardManager;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp(): void
    {
        $this->initClient();
        $this->shardManager = $this->getContainer()->get('oro_pricing.shard_manager');
        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(PriceList::class);
    }

    public function testPostPersistAndPostRemove()
    {
        $priceList = new PriceList();
        $priceList->setName('Test PL');
        $this->shardManager->setEnableSharding(true);
        $this->em->persist($priceList);
        $this->em->flush();
        $shardName = $this->shardManager->getEnabledShardName(ProductPrice::class, ['priceList' => $priceList]);

        $this->assertTrue($this->shardManager->exists($shardName));

        $this->em->remove($priceList);
        $this->em->flush();

        $this->assertFalse($this->shardManager->exists($shardName));
    }
}
