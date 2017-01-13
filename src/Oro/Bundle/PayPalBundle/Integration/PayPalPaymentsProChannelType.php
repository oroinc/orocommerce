<?php

namespace Oro\Bundle\PayPalBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class PayPalPaymentsProChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'paypal_payments_pro';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.paypal.channel_type.payments_pro.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/oropaypal/img/paypal-logo.png';
    }
}
