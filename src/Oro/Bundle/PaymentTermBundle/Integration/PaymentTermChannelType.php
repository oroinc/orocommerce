<?php

namespace Oro\Bundle\PaymentTermBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class PaymentTermChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'payment_term';

    #[\Override]
    public function getLabel()
    {
        return 'oro.paymentterm.channel_type.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/oropaymentterm/img/payment-term-logo.png';
    }
}
