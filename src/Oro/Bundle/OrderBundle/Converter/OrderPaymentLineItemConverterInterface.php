<?php

namespace Oro\Bundle\OrderBundle\Converter;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;

/**
 * Represents a service to convert order line items to a collection of payment line items.
 */
interface OrderPaymentLineItemConverterInterface
{
    /**
     * @param Collection<int, OrderLineItem> $orderLineItems
     *
     * @return Collection<PaymentLineItem>
     */
    public function convertLineItems(Collection $orderLineItems): Collection;
}
