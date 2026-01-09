<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Converts order line items to discount line items.
 *
 * Transforms OrderLineItem entities into DiscountLineItem objects, extracting
 * price and quantity information for discount calculation and application.
 */
class OrderLineItemsToDiscountLineItemsConverter extends AbstractLineItemsToDiscountLineItemsConverter
{
    #[\Override]
    public function convert(array $lineItems): array
    {
        $discountLineItems = [];

        /** @var OrderLineItem[] $lineItems */
        foreach ($lineItems as $lineItem) {
            $discountLineItem = $this->createDiscountLineItem($lineItem);
            if (!$discountLineItem) {
                continue;
            }

            if (!$lineItem->getPrice()) {
                continue;
            }

            $discountLineItem->setPrice($lineItem->getPrice());
            $discountLineItem->setSubtotal($lineItem->getPrice()->getValue() * $lineItem->getQuantity());

            $discountLineItems[] = $discountLineItem;
        }

        return $discountLineItems;
    }
}
