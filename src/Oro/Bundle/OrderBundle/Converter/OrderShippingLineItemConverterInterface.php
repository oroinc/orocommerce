<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;

/**
 * Represents a service to convert order line items to a collection of shipping line items.
 */
interface OrderShippingLineItemConverterInterface
{
    /**
     * @param Collection<int, OrderLineItem> $orderLineItems
     *
     * @return Collection<ShippingLineItem>
     */
    public function convertLineItems(Collection $orderLineItems): Collection;
}
