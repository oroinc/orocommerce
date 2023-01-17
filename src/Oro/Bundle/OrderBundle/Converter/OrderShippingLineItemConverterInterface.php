<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;

/**
 * Represents a service to convert order line items to a collection of shipping line items.
 */
interface OrderShippingLineItemConverterInterface
{
    /**
     * @param Collection<int, OrderLineItem> $orderLineItems
     *
     * @return ShippingLineItemCollectionInterface
     */
    public function convertLineItems(Collection $orderLineItems): ShippingLineItemCollectionInterface;
}
