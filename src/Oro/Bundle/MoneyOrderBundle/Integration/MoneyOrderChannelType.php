<?php

namespace Oro\Bundle\MoneyOrderBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Defines the Money Order payment channel type for integration with the payment system.
 *
 * This class implements the channel type provider for Money Order payments, allowing the system
 * to recognize and manage Money Order as a payment method. It provides the channel label and icon
 * used in the admin interface to identify Money Order payment channels.
 */
class MoneyOrderChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    public const TYPE = 'money_order';

    #[\Override]
    public function getLabel()
    {
        return 'oro.money_order.channel_type.label';
    }

    #[\Override]
    public function getIcon()
    {
        return 'bundles/oromoneyorder/img/money-order-icon.png';
    }
}
