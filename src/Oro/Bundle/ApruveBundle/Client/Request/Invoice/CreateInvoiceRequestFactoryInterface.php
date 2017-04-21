<?php

namespace Oro\Bundle\ApruveBundle\Client\Request\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Model\Invoice\ApruveInvoiceInterface;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;

interface CreateInvoiceRequestFactoryInterface
{
    /**
     * @param ApruveInvoiceInterface $apruveInvoice
     *
     * @return ApruveRequest
     */
    public function create(ApruveInvoiceInterface $apruveInvoice);
}
