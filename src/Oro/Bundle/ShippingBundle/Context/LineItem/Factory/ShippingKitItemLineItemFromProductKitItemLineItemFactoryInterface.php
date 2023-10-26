<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;

/**
 * Describes a factory for creating:
 *  - instance of {@see ShippingKitItemLineItem} by {@see ProductKitItemLineItemPriceAwareInterface};
 *  - collection of {@see ShippingKitItemLineItem} by iterable {@see ProductKitItemLineItemPriceAwareInterface}.
 */
interface ShippingKitItemLineItemFromProductKitItemLineItemFactoryInterface
{
    public function create(ProductKitItemLineItemPriceAwareInterface $productKitItemLineItem): ShippingKitItemLineItem;

    /**
     * @param iterable<ProductKitItemLineItemPriceAwareInterface> $productKitItemLineItems
     *
     * @return Collection<ShippingKitItemLineItem>
     */
    public function createCollection(iterable $productKitItemLineItems): Collection;
}
