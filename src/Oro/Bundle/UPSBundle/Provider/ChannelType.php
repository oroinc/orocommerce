<?php

namespace Oro\Bundle\UPSBundle\Provider;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class UPSChannel implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'ups';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.ups.channel_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/oroups/img/ups-logo.gif';
    }
}
