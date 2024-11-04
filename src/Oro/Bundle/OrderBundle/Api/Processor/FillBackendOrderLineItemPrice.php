<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Calculates and sets the price for {@see OrderLineItem}.
 *
 * The calculated price is the result of the following:
 *     a product kit product price taken from a price list + product kit item line items' prices taken
 *     from a context (i.e. prices submitted in a request).
 *
 *  Works only for a product kit line item.
 */
class FillBackendOrderLineItemPrice extends AbstractBackendFillLineItemPrice
{
    #[\Override]
    protected function getOrderLineItem(CustomizeFormDataContext $context): OrderLineItem
    {
        return $context->getData();
    }
}
