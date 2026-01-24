<?php

namespace Oro\Bundle\FlatRateShippingBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Channel type for flat rate shipping integration.
 *
 * Defines the flat rate shipping channel type for the integration framework,
 * providing label and icon information for the integration UI.
 */
class FlatRateChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'flat_rate';

    #[\Override]
    public function getLabel()
    {
        return 'oro.flat_rate.channel_type.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/oroflatrateshipping/img/flat-rate-logo.png';
    }
}
