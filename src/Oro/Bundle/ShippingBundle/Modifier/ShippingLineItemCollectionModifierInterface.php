<?php

namespace Oro\Bundle\ShippingBundle\Modifier;

use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;

interface ShippingLineItemCollectionModifierInterface
{
    /**
     * @param ShippingLineItemCollectionInterface $lineItems
     *
     * @return ShippingLineItemCollectionInterface
     */
    public function modify(ShippingLineItemCollectionInterface $lineItems): ShippingLineItemCollectionInterface;
}
