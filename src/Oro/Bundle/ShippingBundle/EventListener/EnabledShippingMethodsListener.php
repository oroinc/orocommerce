<?php

namespace Oro\Bundle\ShippingBundle\EventListener;

use Oro\Bundle\ShippingBundle\Event\ApplicableMethodsEvent;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Removes views for disabled shipping methods.
 */
class EnabledShippingMethodsListener
{
    private ShippingMethodProviderInterface $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    public function removeDisabledShippingMethodViews(ApplicableMethodsEvent $event): void
    {
        $methodCollection = $event->getMethodCollection();
        $methodViews = $methodCollection->getAllMethodsViews();
        foreach ($methodViews as $methodId => $methodView) {
            $method = $this->shippingMethodProvider->getShippingMethod($methodId);
            if (null !== $method && !$method->isEnabled()) {
                $methodCollection->removeMethodView($methodId);
            }
        }
    }
}
