<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

/**
 * Defines the contract for creating FedEx shipping method type instances.
 */
interface FedexShippingMethodTypeFactoryInterface
{
    public function create(Channel $channel, FedexShippingService $service): ShippingMethodTypeInterface;
}
