<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Calculates and sets the price for an order line item.
 * The calculated price is the result of the following:
 * a product kit product price taken from a price list + product kit item line items' prices
 * taken from a context (i.e. prices submitted in a request).
 * Works only for a product kit line item.
 */
class FillBackendOrderLineItemPrice extends AbstractBackendFillLineItemPrice
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        if (!$context->getForm()->isValid()) {
            return;
        }

        /** @var OrderLineItem $lineItem */
        $lineItem = $context->getData();
        $this->updateLineItemPrice($lineItem);
    }
}
