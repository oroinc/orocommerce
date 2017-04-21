<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Client\Request\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Client\Request\ApruveRequest;
use Oro\Bundle\ApruveBundle\Apruve\Model\Invoice\ApruveInvoiceInterface;

interface CreateInvoiceRequestFactoryInterface
{
    /**
     * @param ApruveInvoiceInterface $apruveInvoice
     *
     * @return ApruveRequest
     */
    public function create(ApruveInvoiceInterface $apruveInvoice);
}
