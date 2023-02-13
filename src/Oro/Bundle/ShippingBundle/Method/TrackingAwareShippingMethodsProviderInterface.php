<?php

namespace Oro\Bundle\ShippingBundle\Method;

/**
 * Represents a service to get tracking aware shipping methods.
 */
interface TrackingAwareShippingMethodsProviderInterface
{
    /**
     * @return ShippingMethodInterface[] [shipping method identifier => shipping method, ...]
     */
    public function getTrackingAwareShippingMethods(): array;
}
