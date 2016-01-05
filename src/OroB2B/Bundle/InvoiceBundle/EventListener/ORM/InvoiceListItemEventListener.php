<?php

namespace OroB2B\Bundle\InvoiceBundle\EventListener\ORM;

use Doctrine\ORM\Event\PreFlushEventArgs;
use OroB2B\Bundle\InvoiceBundle\Entity\InvoiceLineItem;

class InvoiceListItemEventListener
{
    /**
     * @param InvoiceLineItem $lineItem
     * @param PreFlushEventArgs $event
     */
    public function preFlush(InvoiceLineItem $lineItem, PreFlushEventArgs $event)
    {
        $lineItem->updateItemInformation();
        $invoice = $lineItem->getInvoice();

//        if ($invoice->getId()) {
//            $invoice->requireUpdate();
//
//            $metadata = $event->getEntityManager()->getClassMetadata(get_class($invoice));
//            $event->getEntityManager()->getUnitOfWork()->recomputeSingleEntityChangeSet($metadata, $invoice);
//        }
    }
}
