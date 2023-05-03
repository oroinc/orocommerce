<?php

namespace Oro\Bundle\ShippingBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

/**
 * Represents a factory to create a shipping method for a specific integration channel.
 */
interface IntegrationShippingMethodFactoryInterface
{
    public function create(Channel $channel): ShippingMethodInterface;
}
