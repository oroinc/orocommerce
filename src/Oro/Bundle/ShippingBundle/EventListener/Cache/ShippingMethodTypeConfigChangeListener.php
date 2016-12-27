<?php

namespace Oro\Bundle\ShippingBundle\EventListener\Cache;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;

/**
 * Class ShippingMethodTypeConfigChangeListener
 *
 * @package Oro\Bundle\ShippingBundle\EventListener\Cache
 */
class ShippingMethodTypeConfigChangeListener
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

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        if ($args->getEntity() instanceof ShippingMethodTypeConfig) {
            /** @var ShippingMethodTypeConfig $shippingMethodType */
            $shippingMethodType = $args->getEntity();

            if ($shippingMethodType->getMethodConfig()->getMethod() === FlatRateShippingMethod::IDENTIFIER) {
                $this->priceCache->deleteAllPrices();
            }
        }
    }

}
