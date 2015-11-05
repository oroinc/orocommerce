<?php

namespace OroB2B\Bundle\InvoiceBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use OroB2B\Bundle\InvoiceBundle\Doctrine\ORM\InvoiceNumberGeneratorInterface;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;

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
    public function prePersist(Invoice $invoice, LifecycleEventArgs $event)
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        if (is_null($invoice->getInvoiceDate())) {
            $invoice->setInvoiceDate($now);
            $invoice->setCreatedAt($now);
            $invoice->setUpdatedAt($now);
            $invoice->setPaymentDueDate($now);
        }
    }

    /**
     * @param Invoice $invoice
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Invoice $invoice, PreUpdateEventArgs $event)
    {
        $invoice->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
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
