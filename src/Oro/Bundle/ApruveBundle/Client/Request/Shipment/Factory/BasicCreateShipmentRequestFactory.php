<?php

namespace Oro\Bundle\ApruveBundle\Client\Request\Shipment\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveShipment;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;

class BasicCreateShipmentRequestFactory implements CreateShipmentRequestFactoryInterface
{
    const METHOD = 'POST';
    const URI = '/invoices/%s/shipments';

    /**
     * {@inheritdoc}
     */
    public function create(ApruveShipment $apruveShipment, $apruveInvoiceId)
    {
        return new ApruveRequest(self::METHOD, $this->buildUri($apruveInvoiceId), $apruveShipment);
    }

    /**
     * @param string $apruveInvoiceId
     *
     * @return string
     */
    protected function buildUri($apruveInvoiceId)
    {
        return sprintf(self::URI, $apruveInvoiceId);
    }
}
