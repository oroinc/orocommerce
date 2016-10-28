<?php

namespace Oro\Bundle\UPSBundle\EventListener;

use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodProvider;

class ShippingMethodConfigDataListener
{
    /**
     * @var UPSShippingMethodProvider
     */
    protected $provider;

    /**
     * @param UPSShippingMethodProvider $provider
     */
    public function __construct(UPSShippingMethodProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param ShippingMethodConfigDataEvent $event
     */
    public function onGetConfigData(ShippingMethodConfigDataEvent $event)
    {
        if ($this->provider->hasShippingMethod($event->getMethodIdentifier())) {
            $event->setTemplate('OroUPSBundle::config.html.twig');
        }
    }
}
