<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\Collection;

/**
 * Provide possibility to get product line items from source entity.
 */
interface ProductLineItemsHolderInterface
{
    /**
     * @return ProductLineItemInterface[]|Collection
     */
    public function getLineItems();
}
