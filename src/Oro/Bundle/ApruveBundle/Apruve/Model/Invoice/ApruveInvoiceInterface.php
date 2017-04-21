<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveEntityInterface;

interface ApruveInvoiceInterface extends ApruveEntityInterface
{
    /**
     * @return string
     */
    public function getApruveOrderId();
}
