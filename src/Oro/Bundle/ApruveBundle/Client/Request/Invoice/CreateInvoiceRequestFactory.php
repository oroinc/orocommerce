<?php

namespace Oro\Bundle\ApruveBundle\Client\Request\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Model\Invoice\ApruveInvoiceInterface;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;

class CreateInvoiceRequestFactory implements CreateInvoiceRequestFactoryInterface
{
    const METHOD = 'POST';
    const URI = '/orders/%s/invoices';

    /**
     * {@inheritdoc}
     */
    public function create(ApruveInvoiceInterface $apruveInvoice)
    {
        return new ApruveRequest(self::METHOD, $this->buildUri($apruveInvoice), $apruveInvoice);
    }

    /**
     * @param ApruveInvoiceInterface $apruveInvoice
     *
     * @return string
     */
    protected function buildUri(ApruveInvoiceInterface $apruveInvoice)
    {
        return sprintf(self::URI, $apruveInvoice->getApruveOrderId());
    }
}
