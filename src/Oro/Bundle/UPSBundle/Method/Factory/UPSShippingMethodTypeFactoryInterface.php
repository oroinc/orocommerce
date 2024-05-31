<?php

namespace Oro\Bundle\UPSBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodType;

/**
 * Interface for UPS Shipping Method Type Factory
 */
interface UPSShippingMethodTypeFactoryInterface
{
    public function create(Channel $channel, ShippingService $service): UPSShippingMethodType;
}
