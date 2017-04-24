<?php

namespace Oro\Bundle\UPSBundle\EventListener;

use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class ShippingMethodConfigDataListener
{
    const TEMPLATE = 'OroUPSBundle::UPSMethodWithOptions.html.twig';

    /**
     * @var ShippingMethodProviderInterface
     */
    protected $provider;

    /**
     * @param ShippingMethodProviderInterface $provider
     */
    public function __construct(ShippingMethodProviderInterface $provider)
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
