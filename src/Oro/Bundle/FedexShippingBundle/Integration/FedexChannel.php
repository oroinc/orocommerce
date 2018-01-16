<?php

namespace Oro\Bundle\FedexShippingBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class FedexChannel implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'fedex';

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return 'oro.fedex.integration.channel.label';
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon()
    {
        return 'bundles/orofedexshipping/img/fedex-logo.png';
    }
}
