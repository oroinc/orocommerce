<?php

namespace Oro\Bundle\ApruveBundle\Apruve\Model\Invoice;

use Oro\Bundle\ApruveBundle\Apruve\Model\AbstractApruveEntity;

class ApruveInvoice extends AbstractApruveEntity implements ApruveInvoiceInterface
{
    const ORDER_ID = 'order_id';

    /**
     * @return string
     */
    public function getApruveOrderId()
    {
        return $this->data[self::ORDER_ID];
    }
}
