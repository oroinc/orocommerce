<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;

/**
 * Adds checkout line items basic data.
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
        if (!($firstLineItem instanceof CheckoutLineItem)) {
            throw new \LogicException(
                sprintf('%s entity was expected, got %s', CheckoutLineItem::class, \get_class($firstLineItem))
            );
        }

        /** @var CheckoutLineItem[] $lineItems */
        foreach ($lineItems as $lineItem) {
            $lineItemData = [
                'notes' => (string) $lineItem->getComment(),
            ];

            $currentLineItemData = $event->getDataForLineItem($lineItem->getEntityIdentifier());
            if (empty($currentLineItemData['name'])) {
                $lineItemData['name'] = $lineItem->getFreeFormProduct();
            }

            $event->addDataForLineItem($lineItem->getEntityIdentifier(), $lineItemData);
        }
    }
}
