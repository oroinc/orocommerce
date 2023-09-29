<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

/**
 * Describes a factory for creating:
 *  - instance of {@see ShippingLineItem} by {@see ProductLineItemInterface};
 *  - collection of {@see ShippingLineItem} by iterable {@see ProductLineItemInterface}.
 */
interface ShippingLineItemFromProductLineItemFactoryInterface
{
    public function create(ProductLineItemInterface $productLineItem): ShippingLineItem;

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
     *
     * @return Collection<ShippingLineItem>
     */
    public function createCollection(iterable $productLineItems): Collection;
}
