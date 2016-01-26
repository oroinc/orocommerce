<?php

namespace OroB2B\Bundle\InvoiceBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

class InvoiceFormListener
{
    /**
     * @param AfterFormProcessEvent $event
     */
    public function afterFlush(AfterFormProcessEvent $event)
    {
        $r = func_get_args();
    }
}
