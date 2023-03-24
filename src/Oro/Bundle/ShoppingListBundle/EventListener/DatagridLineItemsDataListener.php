<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Adds shopping list line items basic data.
 */
class DatagridLineItemsDataListener
{
    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            if (!$lineItem instanceof LineItem) {
                continue;
            }

            $event->addDataForLineItem($lineItem->getEntityIdentifier(), ['notes' => (string)$lineItem->getNotes()]);
        }
    }
}
