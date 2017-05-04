<?php

namespace Oro\Bundle\ApruveBundle\Client\Request\Invoice\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveInvoice;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;

class BasicCreateInvoiceRequestFactory implements CreateInvoiceRequestFactoryInterface
{
    const METHOD = 'POST';
    const URI = '/orders/%s/invoices';

    /**
     * {@inheritdoc}
     */
    public function create(ApruveInvoice $apruveInvoice, $apruveOrderId)
    {
        return new ApruveRequest(self::METHOD, $this->buildUri($apruveOrderId), $apruveInvoice);
    }

    /**
     * @param string $apruveOrderId
     *
     * @return string
     */
    protected function buildUri($apruveOrderId)
    {
        return sprintf(self::URI, $apruveOrderId);
    }
}
