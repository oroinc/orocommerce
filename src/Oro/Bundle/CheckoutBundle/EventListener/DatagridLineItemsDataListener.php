<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;

/**
 * Adds checkout line items basic data.
 */
class DatagridLineItemsDataListener
{
    /**
     * @param DatagridLineItemsDataEvent $event
     */
    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $lineItems = $event->getLineItems();
        if (!$lineItems) {
            return;
        }

        $firstLineItem = reset($lineItems);
        if (!($firstLineItem instanceof CheckoutLineItem)) {
            throw new \LogicException(
                sprintf('%s entity was expected, got %s', CheckoutLineItem::class, \get_class($firstLineItem))
            );
        }

        foreach ($lineItems as $lineItem) {
            $event->addDataForLineItem($lineItem->getEntityIdentifier(), ['notes' => (string)$lineItem->getComment()]);
        }
    }
}
