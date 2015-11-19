<?php

namespace OroB2B\Bundle\CatalogBundle\EventListener;

use OroB2B\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

class ProductDataConverterEventListener
{
    /**
     * @param ProductDataConverterEvent $event
     */
    public function onBackendHeader(ProductDataConverterEvent $event)
    {
        $data = $event->getData();
        $data[] = AbstractProductImportEventListener::CATEGORY_KEY;
        $event->setData($data);
    }
}
