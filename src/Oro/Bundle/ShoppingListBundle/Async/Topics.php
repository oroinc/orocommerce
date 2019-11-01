<?php

namespace Oro\Bundle\ShoppingListBundle\Async;

/**
 * MQ Topics for Shopping List bundle
 */
class Topics
{
    public const INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_PRODUCT
        = 'oro_shopping_list.invalidate_totals_by_inventory_status_per_product';
    public const INVALIDATE_TOTALS_BY_INVENTORY_STATUS_PER_WEBSITE
        = 'oro_shopping_list.invalidate_totals_by_inventory_status_per_website';
}
