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
        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $firstLineItem = reset($lineItems);
        if (!($firstLineItem instanceof LineItem)) {
            throw new \LogicException(
                sprintf('%s entity was expected, got %s', LineItem::class, \get_class($firstLineItem))
            );
        }

        foreach ($lineItems as $lineItem) {
            $event->addDataForLineItem($lineItem->getEntityIdentifier(), ['notes' => (string)$lineItem->getNotes()]);
        }
    }
}
