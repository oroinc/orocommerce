<?php

namespace Oro\Bundle\PayPalBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class PayPalPayflowGatewayChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'paypal_payflow_gateway';

    #[\Override]
    public function getLabel()
    {
        return 'oro.paypal.channel_type.payflow_gateway.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/oropaypal/img/paypal-logo.png';
    }
}
