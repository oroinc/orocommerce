<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Stub;

use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingMethodStub;

class TrackingAwareShippingMethodStub extends ShippingMethodStub implements ShippingTrackingAwareInterface
{
    /**
     * {@inheritDoc}
     */
    public function getTrackingLink(string $number): ?string
    {
        return null;
    }
}
