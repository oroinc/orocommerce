<?php

namespace Oro\Bundle\PaymentTermBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Integration channel type for payment term payment method.
 *
 * This channel type defines the payment term as an integration channel within the Oro integration framework,
 * providing a label and icon for display in the admin interface.
 */
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
