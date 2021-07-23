<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener\FrontendLineItemsGrid;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Adds data needed for displaying line items simple rows.
 */
class LineItemsSimpleOnResultAfterListener
{
    public function onResultAfter(OrmResultAfter $event): void
    {
        foreach ($event->getRecords() as $record) {
            /** @var ProductLineItemInterface[] $lineItems */
            $lineItems = $record->getValue('lineItemsByIds') ?? [];
            if (count($lineItems) !== 1) {
                // Skips grouped rows.
                continue;
            }

            $firstLineItem = reset($lineItems);
            if (!$firstLineItem instanceof ProductLineItemInterface) {
                throw new \LogicException(
                    sprintf(
                        'Element lineItemsByIds was expected to contain %s objects',
                        ProductLineItemInterface::class
                    )
                );
            }

            $lineItemsData = $record->getValue('lineItemsDataByIds') ?? [];
            if (count($lineItemsData) !== 1) {
                throw new \LogicException('Element lineItemsDataByIds was expected to contain one item');
            }

            $product = $firstLineItem->getProduct();
            // 1. If configurable line item is only one, then it should be marked as simple.
            // 2. If line item is an empty matrix configurable then it should remain marked as configurable.
            $record->setValue('isConfigurable', $product ? $product->isConfigurable() : false);

            foreach (reset($lineItemsData) as $name => $value) {
                $record->setValue($name, $value);
            }
        }
    }
}
