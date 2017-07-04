<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

class OrderLineItemsToDiscountLineItemsConverter extends AbstractLineItemsToDiscountLineItemsConverter
{
    /**
     * {@inheritdoc}
     */
    public function convert(array $lineItems): array
    {
        $discountLineItems = [];

        /** @var OrderLineItem[] $orderLineItems */
        foreach ($lineItems as $lineItem) {
            $discountLineItem = $this->createDiscountLineItem($lineItem);
            if (!$discountLineItem) {
                continue;
            }

            $discountLineItem->setPrice($lineItem->getPrice());
            $discountLineItem->setSubtotal($lineItem->getPrice()->getValue() * $lineItem->getQuantity());

            $discountLineItems[] = $discountLineItem;
        }

        return $discountLineItems;
    }
}
