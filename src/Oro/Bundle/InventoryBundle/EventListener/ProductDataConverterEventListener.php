<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
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
        if (!in_array(UpcomingProductProvider::IS_UPCOMING, $data)) {
            $data[] = UpcomingProductProvider::IS_UPCOMING;
            $event->setData($data);
        }
    }
}
