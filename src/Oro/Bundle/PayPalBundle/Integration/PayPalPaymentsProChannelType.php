<?php

namespace Oro\Bundle\PayPalBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Defines the PayPal Payments Pro integration channel type.
 *
 * Provides channel configuration and branding for PayPal Payments Pro payment method,
 * including label and icon resources for the integration interface.
 */
class PayPalPaymentsProChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'paypal_payments_pro';

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
