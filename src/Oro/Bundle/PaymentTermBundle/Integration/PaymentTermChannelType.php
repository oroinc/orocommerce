<?php

namespace Oro\Bundle\PaymentTermBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class PaymentTermChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'payment_term';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.paymentterm.channel_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return '';
    }
}
