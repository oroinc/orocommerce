<?php

namespace OroB2B\Bundle\InvoiceBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

use OroB2B\Bundle\InvoiceBundle\Entity\Invoice;
use OroB2B\Bundle\PricingBundle\Provider\LineItemsSubtotalProvider;

class InvoiceFormListener
{
    /**
     * @var LineItemsSubtotalProvider
     */
    protected $subtotalProvider;

    /**
     * @param LineItemsSubtotalProvider $subtotalProvider
     */
    public function __construct(LineItemsSubtotalProvider $subtotalProvider)
    {
        $this->subtotalProvider = $subtotalProvider;
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function beforeFlush(AfterFormProcessEvent $event)
    {
        /** @var Invoice $invoice */
        $invoice = $event->getData();

        $subtotal = $this->subtotalProvider->getSubtotal($invoice);
        $invoice->setSubtotal($subtotal->getAmount());

        foreach ($invoice->getLineItems() as $lineItem) {
            $lineItem->updateItemInformation();
        }
    }
}
