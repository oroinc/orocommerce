<?php

namespace Oro\Bundle\DPDBundle\Method\Factory;

use Oro\Bundle\DPDBundle\Entity\ShippingService;
use Oro\Bundle\DPDBundle\Method\DPDHandler;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

interface DPDHandlerFactoryInterface
{
    /**
     * @param Channel         $channel
     * @param ShippingService $service
     *
     * @return DPDHandler
     */
    public function create(Channel $channel, ShippingService $service);
}
