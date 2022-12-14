<?php

namespace Oro\Bundle\ShippingBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Multi Shipping integration channel.
 */
class MultiShippingChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    public const TYPE = 'multi_shipping';

    public function getLabel(): string
    {
        return 'oro.multi_shipping_method.channel_type.label';
    }

    public function getIcon(): string
    {
        return 'bundles/oroshipping/img/multi-shipping-logo.png';
    }
}
