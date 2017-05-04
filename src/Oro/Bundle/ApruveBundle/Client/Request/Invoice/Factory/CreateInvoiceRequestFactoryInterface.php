<?php

namespace Oro\Bundle\ApruveBundle\Client\Request\Invoice\Factory;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveInvoice;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;

interface CreateInvoiceRequestFactoryInterface
{
    /**
     * @param ApruveInvoice $apruveInvoice
     * @param string        $apruveOrderId
     *
     * @return ApruveRequest
     */
    public function create(ApruveInvoice $apruveInvoice, $apruveOrderId);
}
