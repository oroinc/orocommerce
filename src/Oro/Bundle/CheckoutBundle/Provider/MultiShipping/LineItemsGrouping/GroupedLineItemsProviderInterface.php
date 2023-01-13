<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping;

use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Basic interface for LineItemsGrouping logic.
 */
interface GroupedLineItemsProviderInterface
{
    public function getGroupedLineItems(ProductLineItemsHolderInterface $entity): array;
}
