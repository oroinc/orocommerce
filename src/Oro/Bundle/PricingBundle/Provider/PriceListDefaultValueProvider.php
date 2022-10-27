<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\PricingBundle\Sharding\ShardManager;

/**
 * Provides default price list or null depending on sharding state.
 *
 * Needed for datagrid filters, e.g. see priceListName in product-prices-grid
 */
class PriceListDefaultValueProvider
{
    /**
     * @var PriceListProvider
     */
    private $priceListProvider;

    /**
     * @var ShardManager
     */
    private $shardManager;

    public function __construct(PriceListProvider $priceListProvider, ShardManager $shardManager)
    {
        $this->priceListProvider = $priceListProvider;
        $this->shardManager = $shardManager;
    }

    /**
     * @return int|null
     */
    public function getDefaultPriceListId()
    {
        if ($this->shardManager->isShardingEnabled()) {
            return $this->priceListProvider->getDefaultPriceListId();
        }

        return null;
    }
}
