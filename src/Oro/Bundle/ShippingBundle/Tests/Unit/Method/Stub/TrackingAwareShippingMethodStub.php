<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Stub;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;

class TrackingAwareShippingMethodStub implements ShippingMethodInterface, ShippingTrackingAwareInterface
{
    public function isGrouped()
    {
        return null;
    }

    public function getIdentifier()
    {
        return null;
    }

    public function getLabel()
    {
        return null;
    }

    public function getTypes()
    {
        return null;
    }

    public function getType($identifier)
    {
        return null;
    }

    public function getOptionsConfigurationFormType()
    {
        return null;
    }

    public function getSortOrder()
    {
        return null;
    }

    public function getTrackingLink($number)
    {
        return null;
    }
}
