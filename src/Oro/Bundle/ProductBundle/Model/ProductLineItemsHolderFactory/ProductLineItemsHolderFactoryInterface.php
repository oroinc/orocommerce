<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderFactory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;

/**
 * Interface for the factory creating a product line items holder from the specified line items collection.
 * Useful when it is needed to create a line items holder with only part of line items from the original holder.
 */
interface ProductLineItemsHolderFactoryInterface
{
    /**
     * @param Collection<ProductLineItemInterface>|array<ProductLineItemInterface> $lineItems
     *
     * @return ProductLineItemsHolderInterface
     */
    public function createFromLineItems(Collection|array $lineItems): ProductLineItemsHolderInterface;
}
