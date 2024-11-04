<?php

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;

/**
 * This event allows to collect from kit items line items the data needed for the records of datagrid.
 */
class DatagridKitItemLineItemsDataEvent extends DatagridLineItemsDataEvent
{
    /** @var string */
    public const NAME = 'oro_product.datagrid_kit_item_line_items_data';

    /** @var array<int,ProductKitItemLineItemInterface> */
    protected array $lineItems;

    /**
     * @return array<int,ProductKitItemLineItemInterface> Line items indexed by ID.
     */
    #[\Override]
    public function getLineItems(): array
    {
        return $this->lineItems;
    }
}
