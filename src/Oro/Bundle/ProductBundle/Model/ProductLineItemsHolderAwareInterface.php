<?php

namespace Oro\Bundle\ProductBundle\Model;

/**
 * Interface for a product line item aware of its holder.
 */
interface ProductLineItemsHolderAwareInterface
{
    public function getLineItemsHolder(): ?ProductLineItemsHolderInterface;
}
