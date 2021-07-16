<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;

/**
 * Adds edit-related line items data.
 */
class DatagridLineItemsDataEditListener
{
    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            $product = $lineItem->getProduct();

            // Units list is needed for units dropdown.
            $event->addDataForLineItem(
                $lineItem->getEntityIdentifier(),
                ['units' => $this->getProductUnitsList($product)]
            );
        }
    }

    private function getProductUnitsList(Product $product): array
    {
        $list = [];
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            if (!$unitPrecision->isSell()) {
                continue;
            }

            $list[$unitPrecision->getUnit()->getCode()] = [
                'precision' => $unitPrecision->getPrecision(),
            ];
        }

        return $list;
    }
}
