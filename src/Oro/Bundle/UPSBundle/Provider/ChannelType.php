<?php

namespace Oro\Bundle\UPSBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * UPS Channel Type provider
 */
class ChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    public const TYPE = 'ups';

    #[\Override]
    public function getLabel()
    {
        return 'oro.ups.channel_type.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/oroups/img/ups-logo.gif';
    }
}
