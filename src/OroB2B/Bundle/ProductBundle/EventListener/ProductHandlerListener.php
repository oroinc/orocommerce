<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductHandlerListener
{
    public function onBeforeFlush(AfterFormProcessEvent $event)
    {
        $data = $event->getData();

        if ($data instanceof Product && false === $data->getHasVariants()) {
            $data->getVariantLinks()->clear();
        }
    }
}
