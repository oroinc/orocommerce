<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Stub;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;

class TrackingAwareShippingMethodStub implements ShippingMethodInterface, ShippingTrackingAwareInterface
{
    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function isGrouped()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypes()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getType($identifier)
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackingLink($number)
    {
        return null;
    }
}
