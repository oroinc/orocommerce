<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Context\PaymentKitItemLineItem;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemPriceAwareInterface;

/**
 * Describes a factory for creating:
 *  - instance of {@see PaymentKitItemLineItem} by {@see ProductKitItemLineItemPriceAwareInterface};
 *  - collection of {@see PaymentKitItemLineItem} by iterable {@see ProductKitItemLineItemPriceAwareInterface}.
 */
interface PaymentKitItemLineItemFromProductKitItemLineItemFactoryInterface
{
    public function create(ProductKitItemLineItemPriceAwareInterface $productKitItemLineItem): PaymentKitItemLineItem;

    /**
     * @param iterable<ProductKitItemLineItemPriceAwareInterface> $productKitItemLineItems
     *
     * @return Collection<PaymentKitItemLineItem>
     */
    public function createCollection(iterable $productKitItemLineItems): Collection;
}
