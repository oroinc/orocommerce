<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
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
    public const SUBMITTED_PRICE = 'order_line_item_submitted_price';

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        /** @var OrderLineItem|OrderProductKitItemLineItem $lineItem */
        $lineItem = $context->getData();
        $price = $this->getPrice($lineItem);
        if ($price) {
            $context->set(static::SUBMITTED_PRICE, $price);
        }
        $lineItem->setValue(0);
        $lineItem->setCurrency(null);
    }

    private function getPrice(OrderLineItem|OrderProductKitItemLineItem $lineItem): ?array
    {
        $value = $lineItem->getValue();
        $currency = $lineItem->getCurrency();
        if (null === $value && null === $currency) {
            return null;
        }

        return ['value' => $value, 'currency' => $currency];
    }
}
