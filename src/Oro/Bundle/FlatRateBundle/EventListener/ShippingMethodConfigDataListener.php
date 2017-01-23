<?php

namespace Oro\Bundle\FlatRateBundle\EventListener;

use Oro\Bundle\FlatRateBundle\Method\FlatRateMethodProvider;
use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;

class ShippingMethodConfigDataListener
{
    const TEMPLATE = 'OroFlatRateBundle::method/flatRateMethodWithOptions.html.twig';

    /** @var FlatRateMethodProvider */
    private $provider;

    /**
     * @param FlatRateMethodProvider $provider
     */
    public function __construct(FlatRateMethodProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param ShippingMethodConfigDataEvent $event
     */
    public function onGetConfigData(ShippingMethodConfigDataEvent $event)
    {
        if ($this->provider->hasShippingMethod($event->getMethodIdentifier())) {
            $event->setTemplate(self::TEMPLATE);
        }
    }
}
