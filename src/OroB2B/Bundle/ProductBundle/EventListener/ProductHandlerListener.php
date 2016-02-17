<?php

namespace OroB2B\Bundle\ProductBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductHandlerListener
{
    /**
     * @param AfterFormProcessEvent $event
     */
    public function onBeforeFlush(AfterFormProcessEvent $event)
    {
        $data = $event->getData();

        if ($data instanceof Product) {
            $variantFields = $data->getVariantFields();
            $hasVariants = !empty($variantFields);
            $data->setHasVariants($hasVariants);

            if (!$hasVariants) {
                $data->getVariantLinks()->clear();
            }
        }
    }
}
