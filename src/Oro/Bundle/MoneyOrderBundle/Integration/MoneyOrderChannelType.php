<?php

namespace Oro\Bundle\MoneyOrderBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

class MoneyOrderChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    const TYPE = 'money_order';

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return 'oro.money_order.channel_type.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon()
    {
        return 'bundles/oromoneyorder/img/money-order-icon.png';
    }
}
