<?php

namespace Oro\Bundle\FlatRateShippingBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class FlatRateChannelType implements ChannelInterface, IconAwareIntegrationInterface
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
