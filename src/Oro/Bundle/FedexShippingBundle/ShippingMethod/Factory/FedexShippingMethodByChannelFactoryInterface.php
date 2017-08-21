<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory;

use Oro\Bundle\FedexShippingBundle\ShippingMethod\FedexShippingMethod;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface FedexShippingMethodByChannelFactoryInterface
{
    /**
     * @param Channel $channel
     *
     * @return FedexShippingMethod
     */
    public function create(Channel $channel): FedexShippingMethod;
}
