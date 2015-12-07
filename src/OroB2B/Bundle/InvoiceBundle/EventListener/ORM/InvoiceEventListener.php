<?php

namespace OroB2B\Bundle\InvoiceBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use OroB2B\Bundle\InvoiceBundle\Doctrine\ORM\InvoiceNumberGeneratorInterface;
use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\PricingBundle\Provider\LineItemsSubtotalProvider;

class InvoiceEventListener
{
    /**
     * @var InvoiceNumberGeneratorInterface
     */
    private $invoiceNumberGenerator;

    /**
     * @var LineItemsSubtotalProvider
     */
    private $lineItemsSubtotalProvider;

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
     * @param LineItemsSubtotalProvider $lineItemsSubtotalProvider
     * @return $this
     */
    public function setLineItemsSubtotalProvider($lineItemsSubtotalProvider)
    {
        $this->lineItemsSubtotalProvider = $lineItemsSubtotalProvider;

        return $this;
    }

    /**
     * @param Invoice $invoice
     * @param LifecycleEventArgs $event
     */
    public function prePersist(Invoice $invoice, LifecycleEventArgs $event)
    {
        $this->fillSubtotal($invoice);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $invoice->setCreatedAt($now);
        $invoice->setUpdatedAt($now);
    }

    /**
     * @param Invoice $invoice
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(Invoice $invoice, PreUpdateEventArgs $event)
    {
        $this->fillSubtotal($invoice);
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

    /**
     * @param Invoice $invoice
     */
    protected function fillSubtotal(Invoice $invoice)
    {
        $subtotal = $this->lineItemsSubtotalProvider->getSubtotal($invoice);

        $invoice->setSubtotal($subtotal->getAmount());
    }
}
