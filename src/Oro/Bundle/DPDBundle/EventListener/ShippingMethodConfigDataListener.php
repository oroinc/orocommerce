<?php

namespace Oro\Bundle\DPDBundle\EventListener;

use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\DPDBundle\Method\DPDShippingMethodProvider;

class ShippingMethodConfigDataListener
{
    const TEMPLATE = 'OroDPDBundle::DPDMethodWithOptions.html.twig';

    /**
     * @var DPDShippingMethodProvider
     */
    protected $provider;

    /**
     * @param DPDShippingMethodProvider $provider
     */
    public function __construct(DPDShippingMethodProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param ShippingMethodConfigDataEvent $event
     */
    public function onGetConfigData(ShippingMethodConfigDataEvent $event)
    {
        if ($this->provider->hasShippingMethod($event->getMethodIdentifier())) {
            $event->setTemplate(static::TEMPLATE);
        }
    }
}
