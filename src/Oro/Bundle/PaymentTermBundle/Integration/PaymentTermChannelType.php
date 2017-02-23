<?php

namespace Oro\Bundle\PaymentTermBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;

class PaymentTermChannelType implements ChannelInterface
{
    const TYPE = 'payment_term';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.paymentterm.channel_type.label';
    }
}
