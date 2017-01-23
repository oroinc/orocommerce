<?php

namespace Oro\Bundle\FlatRateBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class FlatRateChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'flat_rate';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.flat_rate.channel_type.label';
    }

    /**
     * TODO: add icon
     *
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/oroflatrate/img/flat-logo.gif';
    }
}
