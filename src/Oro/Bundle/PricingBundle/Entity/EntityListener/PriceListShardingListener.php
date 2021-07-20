<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
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
     * @var array
     */
    protected $priceListsCreate = [];

    /**
     * @var array
     */
    protected $priceListsDelete = [];

    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof PriceList) {
            $this->priceListsCreate[] = $entity->getId();
        }
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof PriceList) {
            $this->priceListsDelete[] = $entity->getId();
        }
    }

    public function postFlush(PostFlushEventArgs $args)
    {
        foreach ($this->priceListsCreate as $priceList) {
            $shardName = $this->shardManager->getEnabledShardName(ProductPrice::class, ['priceList' => $priceList]);
            if (!$this->shardManager->exists($shardName)) {
                $this->shardManager->create(ProductPrice::class, $shardName);
            }
        }
        foreach ($this->priceListsDelete as $priceList) {
            $shardName = $this->shardManager->getEnabledShardName(ProductPrice::class, ['priceList' => $priceList]);
            $this->shardManager->delete($shardName);
        }

        $this->priceListsCreate = [];
        $this->priceListsDelete = [];
    }
}
