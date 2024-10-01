<?php

namespace Oro\Bundle\FixedProductShippingBundle\Integration;

use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\IconAwareIntegrationInterface;

/**
 * Fixed Product integration channel.
 */
class FixedProductChannelType implements ChannelInterface, IconAwareIntegrationInterface
{
    public const TYPE = 'fixed_product';

    #[\Override]
    public function getLabel(): string
    {
        return 'oro.fixed_product.channel_type.label';
    }

    #[\Override]
    public function getIcon(): string
    {
        return 'bundles/orofixedproductshipping/img/fixed-product-logo.png';
    }
}
