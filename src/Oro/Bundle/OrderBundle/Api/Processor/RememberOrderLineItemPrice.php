<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Stores the submitted order line item price ("price" and "currency" fields) to the context
 * and sets a fake price to be sure the price value validation will pass.
 * The stored price is used by {@see FillOrderLineItemPrice} processor to validate
 * that it equals to a calculated price.
 */
class RememberOrderLineItemPrice implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var OrderLineItem $lineItem */
        $lineItem = $context->getData();
        $value = $lineItem->getValue();
        $currency = $lineItem->getCurrency();
        if (null !== $value || null !== $currency) {
            FillOrderLineItemPrice::setSubmittedPrice($context, $value, $currency);
        }
        $lineItem->setValue(0.0);
        $lineItem->setCurrency(null);
    }
}
