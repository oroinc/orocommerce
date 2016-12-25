<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\PaymentLineItemCollectionInterface;

interface OrderPaymentLineItemConverterInterface
{
    /**
     * @param OrderLineItem[]|Collection $orderLineItems
     *
     * @return PaymentLineItemCollectionInterface|null
     */
    public function convertLineItems(Collection $orderLineItems);
}
