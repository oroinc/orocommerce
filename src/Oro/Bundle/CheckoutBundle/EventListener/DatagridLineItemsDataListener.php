<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;

/**
 * Adds checkout line items basic data.
 */
class DatagridLineItemsDataListener
{
    public const SKU = 'sku';
    public const NAME = 'name';
    public const NOTES = 'notes';

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
                self::NOTES => (string) $lineItem->getComment(),
            ];

            $currentLineItemData = $event->getDataForLineItem($lineItem->getEntityIdentifier());

            if (empty($currentLineItemData[self::SKU])) {
                $lineItemData[self::SKU] = $lineItem->getProductSku();
            }

            if (empty($currentLineItemData[self::NAME])) {
                $lineItemData[self::NAME] = $lineItem->getFreeFormProduct();
            }

            $event->addDataForLineItem($lineItem->getEntityIdentifier(), $lineItemData);
        }
    }
}
