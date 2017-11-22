<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\ProductBundle\ImportExport\Event\ProductDataConverterEvent;

/**
 * This event listener is used to add "isUpcoming" field to import\export entity headers
 * because this field is deleted from import process by import export bundle
 */
class ProductDataConverterEventListener
{
    /**
     * @param ProductDataConverterEvent $event
     */
    public function modifyBackendHeader(ProductDataConverterEvent $event)
    {
        $data = $event->getData();
        if (!in_array(ProductUpcomingProvider::IS_UPCOMING, $data)) {
            $data[] = ProductUpcomingProvider::IS_UPCOMING;
            $event->setData($data);
        }
    }
}
