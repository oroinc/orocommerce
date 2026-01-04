<?php

namespace Oro\Bundle\PayPalBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class PayPalPaymentsProChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    public const TYPE = 'paypal_payments_pro';

    #[\Override]
    public function getLabel()
    {
        return 'oro.paypal.channel_type.payments_pro.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/oropaypal/img/paypal-logo.png';
    }
}
