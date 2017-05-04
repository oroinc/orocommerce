<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Factory\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveInvoice;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

interface ApruveInvoiceFromResponseFactoryInterface
{
    /**
     * @param RestResponseInterface $restResponse
     *
     * @return ApruveInvoice
     *
     * @throws \Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException
     */
    public function createFromResponse(RestResponseInterface $restResponse);
}
