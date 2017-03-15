<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Shards based on price lists should be created and deleted here
 */
class PriceListShardingListener
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @param ShardManager $shardManager
     */
    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    /**
     * @param PriceList $priceList
     */
    public function postPersist(PriceList $priceList)
    {
        $shardName = $this->shardManager->getShardName(ProductPrice::class, ['priceList' => $priceList]);
        if (!$this->shardManager->exists($shardName)) {
            $this->shardManager->create(ProductPrice::class, $shardName);
        }
    }

    /**
     * @param PriceList $priceList
     */
    public function preRemove(PriceList $priceList)
    {
        $shardName = $this->shardManager->getShardName(ProductPrice::class, ['priceList' => $priceList]);
        $this->shardManager->delete($shardName);
    }
}
