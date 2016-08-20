<?php

namespace Oro\Bundle\InvoiceBundle\EventListener\ORM;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\InvoiceBundle\Doctrine\ORM\InvoiceNumberGeneratorInterface;
use Oro\Bundle\InvoiceBundle\Entity\Invoice;

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
     *
     * Persist invoiceNumber based on entity id
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
}
