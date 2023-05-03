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
            $lineItems = $record->getValue(LineItemsDataOnResultAfterListener::LINE_ITEMS) ?? [];
            if (count($lineItems) !== 1) {
                // Skips grouped rows.
                continue;
            }

            $lineItem = reset($lineItems);
            if (!$lineItem instanceof ProductLineItemInterface) {
                continue;
            }

            $lineItemsData = $record->getValue(LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA) ?? [];
            if (count($lineItemsData) !== 1) {
                throw new \LogicException(
                    sprintf(
                        'Element %s was expected to contain one item',
                        LineItemsDataOnResultAfterListener::LINE_ITEMS_DATA
                    )
                );
            }

            foreach (reset($lineItemsData) as $name => $value) {
                $record->setValue($name, $value);
            }
        }
    }
}
