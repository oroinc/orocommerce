<?php

namespace Oro\Bundle\InvoiceBundle\Doctrine\ORM;

use Oro\Bundle\InvoiceBundle\Entity\Invoice;

class SimpleInvoiceNumberGenerator implements InvoiceNumberGeneratorInterface
{
    /**
     * @param Invoice $invoice
     * @return int
     */
    public function generate(Invoice $invoice)
    {
        return $invoice->getId();
    }
}
