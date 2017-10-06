<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

interface FedexShippingMethodTypeFactoryInterface
{
    /**
     * @param Channel         $channel
     * @param ShippingService $service
     *
     * @return ShippingMethodTypeInterface
     */
    public function create(Channel $channel, ShippingService $service): ShippingMethodTypeInterface;
}
