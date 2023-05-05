<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping;

use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Represents a service to group checkout line items.
 */
interface GroupedLineItemsProviderInterface
{
    public function getGroupedLineItems(ProductLineItemsHolderInterface $entity): array;
}
