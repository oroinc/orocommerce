<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Calculates the price ("value" and "currency" fields) for {@see OrderLineItem},
 * compares the calculated price with a price stored in the context (it is a price submitted by a user)
 * and sets the calculated value to the {@see OrderLineItem} if the prices are equal.
 */
class FillOrderLineItemPrice extends AbstractFillLineItemPrice
{
    #[\Override]
    protected function getOrderLineItem(CustomizeFormDataContext $context): OrderLineItem
    {
        return $context->getData();
    }

    #[\Override]
    protected function getPriceNotFoundErrorMessage(): string
    {
        return $this->translator->trans('oro.order.orderlineitem.product_price.blank', [], 'validators');
    }

    #[\Override]
    protected function getPriceNotMatchErrorMessage($expectedValue): string
    {
        return $this->translator->trans(
            'oro.order.orderlineitem.product_price.not_match',
            ['{{ expected_value }}' => $expectedValue],
            'validators'
        );
    }

    #[\Override]
    protected function getCurrencyNotMatchErrorMessage($expectedValue): string
    {
        return $this->translator->trans(
            'oro.order.orderlineitem.currency.not_match',
            ['{{ expected_value }}' => $expectedValue],
            'validators'
        );
    }

    #[\Override]
    protected function getSubmittedPriceKey(): string
    {
        return RememberOrderLineItemPrice::SUBMITTED_PRICE;
    }
}
