<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod\Factory;

use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

interface FedexShippingMethodTypeFactoryInterface
{
    public function create(Channel $channel, FedexShippingService $service): ShippingMethodTypeInterface;
}
