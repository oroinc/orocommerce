<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Creates and deletes shards based on price lists.
 */
class PriceListShardingListener
{
    private ShardManager $shardManager;
    private array $priceListsCreate = [];
    private array $priceListsDelete = [];

    public function __construct(ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
    }

    public function postPersist(PriceList $priceList): void
    {
        $this->priceListsCreate[] = $priceList->getId();
    }

    public function preRemove(PriceList $priceList): void
    {
        $this->priceListsDelete[] = $priceList->getId();
    }

    public function postFlush(): void
    {
        $priceListsCreate = $this->priceListsCreate;
        $priceListsDelete = $this->priceListsDelete;
        $this->priceListsCreate = [];
        $this->priceListsDelete = [];

        foreach ($priceListsCreate as $priceListId) {
            $shardName = $this->getEnabledShardName($priceListId);
            if (!$this->shardManager->exists($shardName)) {
                $this->shardManager->create(ProductPrice::class, $shardName);
            }
        }
        foreach ($priceListsDelete as $priceListId) {
            $this->shardManager->delete($this->getEnabledShardName($priceListId));
        }
    }

    private function getEnabledShardName(int $priceListId): string
    {
        return $this->shardManager->getEnabledShardName(ProductPrice::class, ['priceList' => $priceListId]);
    }
}
