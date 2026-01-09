<?php

namespace Oro\Bundle\CatalogBundle\EventListener;

use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

/**
 * Handles product data converter events during import/export operations.
 *
 * Listens to product data converter events and adds the category field to the backend header,
 * ensuring that category information is included in the exported product data.
 */
class ProductDataConverterEventListener
{
    public function onBackendHeader(ProductDataConverterEvent $event)
    {
        $data = $event->getData();
        $data[] = AbstractProductImportEventListener::CATEGORY_KEY;
        $event->setData($data);
    }
}
