<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping;

use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Represents a service to group checkout line items.
 */
interface GroupedLineItemsProviderInterface
{
    /**
     * @param ProductLineItemsHolderInterface $entity
     *
     * @return array ['product.category:1' => [line item, ...], ...]
     */
    public function getGroupedLineItems(ProductLineItemsHolderInterface $entity): array;
}
