<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory;

use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

/**
 * Interface for the factory creating a collection of shipping line item models.
 *
 * @deprecated since 5.1
 */
interface ShippingLineItemCollectionFactoryInterface
{
    /**
     * @param ShippingLineItemInterface[] $shippingLineItems
     *
     * @return ShippingLineItemCollectionInterface
     */
    public function createShippingLineItemCollection(array $shippingLineItems): ShippingLineItemCollectionInterface;
}
