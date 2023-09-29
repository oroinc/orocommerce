<?php

namespace Oro\Bundle\PaymentBundle\Context\LineItem\Factory;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Describes a factory for creating:
 *  - instance of {@see PaymentLineItem} by {@see ProductLineItemInterface};
 *  - collection of {@see PaymentLineItem} by iterable {@see ProductLineItemInterface}.
 */
interface PaymentLineItemFromProductLineItemFactoryInterface
{
    public function create(ProductLineItemInterface $productLineItem): PaymentLineItem;

    /**
     * @param iterable<ProductLineItemInterface> $productLineItems
     *
     * @return Collection<PaymentLineItem>
     */
    public function createCollection(iterable $productLineItems): Collection;
}
