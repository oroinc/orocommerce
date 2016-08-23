<?php

namespace Oro\Bundle\InvoiceBundle\Doctrine\ORM;

use Oro\Bundle\InvoiceBundle\Entity\Invoice;

interface InvoiceNumberGeneratorInterface
{
    /**
     * @param Invoice $invoice
     * @return mixed
     */
    public function generate(Invoice $invoice);
}
