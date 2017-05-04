<?php

namespace Oro\Bundle\ApruveBundle\Client\Request\Shipment\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;

interface CreateShipmentRequestFactoryInterface
{
    /**
     * @param ApruveShipment $apruveShipment
     * @param string         $apruveInvoiceId
     *
     * @return ApruveRequest
     */
    public function create(ApruveShipment $apruveShipment, $apruveInvoiceId);
}
