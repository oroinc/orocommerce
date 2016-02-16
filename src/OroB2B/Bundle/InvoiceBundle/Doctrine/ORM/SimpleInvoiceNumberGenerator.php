<?php

namespace OroB2B\Bundle\InvoiceBundle\Doctrine\ORM;

use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;

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
