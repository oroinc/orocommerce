<?php

namespace OroB2B\Bundle\InvoiceBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

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
     */
    public function prePersist(Invoice $invoice)
    {
        $this->fillSubtotal($invoice);

        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $invoice->setCreatedAt($now);
        $invoice->setUpdatedAt($now);
    }

    /**
     * @param Invoice $invoice
     */
    public function preUpdate(Invoice $invoice)
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
        if (null === $invoice->getInvoiceNumber()) {
            $changeSet = [
                'invoiceNumber' => [null, $this->invoiceNumberGenerator->generate($invoice)],
            ];

            $unitOfWork = $event->getEntityManager()->getUnitOfWork();
            $unitOfWork->scheduleExtraUpdate($invoice, $changeSet);
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
