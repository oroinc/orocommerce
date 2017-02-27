<?php

namespace Oro\Bundle\FlatRateShippingBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;

class FlatRateChannelType implements ChannelInterface
{
    const TYPE = 'flat_rate';

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return 'oro.flat_rate.channel_type.label';
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon()
    {
        return 'bundles/oroflatrateshipping/img/flat-rate-logo.png';
    }
}
