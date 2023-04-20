<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\DataGrid\Property\ProductUnitsProperty;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;

/**
 * Adds edit-related line items data.
 */
class DatagridLineItemsDataEditListener
{
    private ProductUnitsProperty $productUnitsProperty;

    public function __construct(ProductUnitsProperty $productUnitsProperty)
    {
        $this->productUnitsProperty = $productUnitsProperty;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        foreach ($event->getLineItems() as $lineItem) {
            $product = $lineItem->getProduct();

            // Units list is needed for units dropdown.
            $event->addDataForLineItem(
                $lineItem->getEntityIdentifier(),
                ['units' => $this->productUnitsProperty->getProductUnits($product)]
            );
        }
    }
}
