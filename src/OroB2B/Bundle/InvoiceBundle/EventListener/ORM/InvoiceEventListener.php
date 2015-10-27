<?php

namespace OroB2B\Bundle\InvoiceBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;
use OroB2B\Bundle\InvoiceBundle\Doctrine\ORM\InvoiceNumberGeneratorInterface;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;

/**
 * Class InvoiceEventListener
 */
class InvoiceEventListener
{
    /**
     * @var InvoiceNumberGeneratorInterface
     */
    private $invoiceNumberGenerator;

    /**
     * @param InvoiceNumberGeneratorInterface $numberGenerator
     * @return $this
     */
    public function setInvoiceNumberGenerator(InvoiceNumberGeneratorInterface $numberGenerator)
    {
        $this->invoiceNumberGenerator = $numberGenerator;

        return $this;
    }

    /**
     * @param Invoice $invoice
     * @param LifecycleEventArgs $event
     */
    public function postPersist(Invoice $invoice, LifecycleEventArgs $event)
    {
        if (is_null($invoice->getInvoiceNumber())) {
            $invoice->setInvoiceNumber($this->invoiceNumberGenerator->generate($invoice));
            $event->getEntityManager()->flush($invoice);
        }
    }
}
