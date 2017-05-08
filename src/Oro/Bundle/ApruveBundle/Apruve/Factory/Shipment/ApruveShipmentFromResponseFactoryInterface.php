<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Factory\Shipment;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

interface ApruveShipmentFromResponseFactoryInterface
{
    /**
     * @param RestResponseInterface $restResponse
     *
     * @return ApruveShipment
     *
     * @throws \Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException
     */
    public function createFromResponse(RestResponseInterface $restResponse);
}
