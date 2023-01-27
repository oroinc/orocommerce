<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * Provides tracking aware shipping methods.
 */
class TrackingAwareShippingMethodsProvider implements TrackingAwareShippingMethodsProviderInterface
{
    private ShippingMethodProviderInterface $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackingAwareShippingMethods(): array
    {
        $result = [];
        $shippingMethods = $this->shippingMethodProvider->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod instanceof ShippingTrackingAwareInterface) {
                $result[$shippingMethod->getIdentifier()] = $shippingMethod;
            }
        }

        return $result;
    }
}
