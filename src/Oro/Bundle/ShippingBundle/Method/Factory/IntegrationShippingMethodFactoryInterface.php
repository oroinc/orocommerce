<?php

namespace Oro\Bundle\ShippingBundle\Method\Factory;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

interface IntegrationShippingMethodFactoryInterface
{
    /**
     * @param Channel $channel
     * @return ShippingMethodInterface
     */
    public function create(Channel $channel);
}
