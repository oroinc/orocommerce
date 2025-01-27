<?php

namespace Oro\Bundle\FedexShippingBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * FedEx integration channel.
 */
class FedexChannel implements ChannelInterface, IconAwareIntegrationInterface
{
    public const string TYPE = 'fedex';

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.fedex.integration.channel.label';
    }

    #[\Override]
    public function getIcon(): string
    {
        return 'bundles/orofedexshipping/img/fedex-logo.png';
    }
}
