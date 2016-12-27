<?php

namespace Oro\Bundle\ShippingBundle\EventListener\Cache;

use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;

class ShippingMethodsConfigsRuleChangeListener
{

    /** @var  ShippingPriceCache */
    private $priceCache;

    /**
     * @param ShippingPriceCache $priceCache
     */
    public function __construct(ShippingPriceCache $priceCache)
    {
        $this->priceCache = $priceCache;
    }

    public function postPersist()
    {
        $this->priceCache->deleteAllPrices();
    }

    public function postRemove()
    {
        $this->priceCache->deleteAllPrices();
    }
}
