<?php

namespace Oro\Bundle\ApruveBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class ApruveChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'apruve';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.apruve.channel_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/oroapruve/img/apruve-logo.png';
    }
}
