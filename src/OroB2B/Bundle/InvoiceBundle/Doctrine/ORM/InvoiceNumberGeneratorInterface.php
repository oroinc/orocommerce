<?php

namespace OroB2B\Bundle\InvoiceBundle\Doctrine\ORM;

use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;

interface InvoiceNumberGeneratorInterface
{
    /**
     * @param Invoice $invoice
     * @return mixed
     */
    public function generate(Invoice $invoice);
}
