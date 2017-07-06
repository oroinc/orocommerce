<?php

namespace Oro\Bundle\ShippingBundle\Method;

class TrackingAwareShippingMethodsProvider implements TrackingAwareShippingMethodsProviderInterface
{
    /**
     * @var ShippingMethodProviderInterface
     */
    private $shippingMethodProvider;

    /**
     * @param ShippingMethodProviderInterface $shippingMethodProvider
     */
    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackingAwareShippingMethods()
    {
        $result = [];
        foreach ($this->shippingMethodProvider->getShippingMethods() as $shippingMethod) {
            if ($shippingMethod instanceof ShippingTrackingAwareInterface) {
                $result[$shippingMethod->getIdentifier()] = $shippingMethod;
            }
        }
        return $result;
    }
}
