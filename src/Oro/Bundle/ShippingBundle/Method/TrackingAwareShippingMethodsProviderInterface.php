<?php

namespace Oro\Bundle\ShippingBundle\Method;

interface TrackingAwareShippingMethodsProviderInterface
{
    /**
     * @return ShippingMethodInterface[]
     */
    public function getTrackingAwareShippingMethods();
}
