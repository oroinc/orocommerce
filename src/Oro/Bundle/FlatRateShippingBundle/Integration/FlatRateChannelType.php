<?php

namespace Oro\Bundle\FlatRateShippingBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;

class FlatRateChannelType implements ChannelInterface
{
    const TYPE = 'flat_rate';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.flat_rate.channel_type.label';
    }
}
