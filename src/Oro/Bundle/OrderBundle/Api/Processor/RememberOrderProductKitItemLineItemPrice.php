<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Stores the submitted price of {@see OrderProductKitItemLineItem} ("price" and "currency" fields) to the context
 * and sets a fake price to be sure the price value validation will pass.
 * The stored price is used by {@see FillOrderProductKitItemLineItemPrice} processor to validate
 * that it equals to a calculated price.
 */
class RememberOrderProductKitItemLineItemPrice implements ProcessorInterface
{
    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var OrderProductKitItemLineItem $kitItemLineItem */
        $kitItemLineItem = $context->getData();
        $value = $kitItemLineItem->getValue();
        $currency = $kitItemLineItem->getCurrency();
        if (null !== $value || null !== $currency) {
            FillOrderProductKitItemLineItemPrice::setSubmittedPrice($context, $value, $currency);
        }
        $kitItemLineItem->setValue(0.0);
        $kitItemLineItem->setCurrency(null);
    }
}
