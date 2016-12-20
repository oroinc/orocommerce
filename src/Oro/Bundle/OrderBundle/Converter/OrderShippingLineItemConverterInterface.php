<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;

interface OrderShippingLineItemConverterInterface
{
    /**
     * @param OrderLineItem[]|Collection $orderLineItems
     *
     * @return ShippingLineItemCollectionInterface|null
     */
    public function convertLineItems(Collection $orderLineItems);
}
