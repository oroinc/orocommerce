<?php

namespace Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Factory;

use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

interface ShippingLineItemCollectionFactoryInterface
{
    /**
     * @param ShippingLineItemInterface[] $shippingLineItems
     *
     * @return ShippingLineItemCollectionInterface
     */
    public function createShippingLineItemCollection(array $shippingLineItems): ShippingLineItemCollectionInterface;
}
