<?php

namespace Oro\Bundle\FedexShippingBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class FedexChannel implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'fedex';

    #[\Override]
    public function getLabel()
    {
        return 'oro.fedex.integration.channel.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/orofedexshipping/img/fedex-logo.png';
    }
}
