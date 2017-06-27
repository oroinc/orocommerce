<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListDiscountContextConverter implements DiscountContextConverterInterface
{
    /**
     * @param ShoppingList $sourceEntity
     * {@inheritdoc}
     */
    public function convert($sourceEntity): DiscountContext
    {
        $discountContext = new DiscountContext();

        // TODO: replace with real subtotal
        // Note, that promotion this class is used in promotion executor which is used in subtotal.
        // Passing subtotals here will cause circular reference
        $discountContext->setSubtotal(random_int(10, 1000));

        foreach ($sourceEntity->getLineItems() as $lineItem) {
            $discountLineItem = new DiscountLineItem();
            // TODO: replace with real price, see how this is done in checkout.
            // Note that SL is also available at backend admin and prices must be actual for owning customer
            $discountLineItem->setPrice(Price::create(random_int(10, 100), 'USD'));
            $discountLineItem->setQuantity($lineItem->getQuantity());
            $discountLineItem->setProduct($lineItem->getProduct());
            $discountLineItem->setProductUnit($lineItem->getProductUnit());
            $discountLineItem->setSourceLineItem($lineItem);
            $discountLineItem->setSubtotal($discountLineItem->getPrice()->getValue() * $lineItem->getQuantity());
            $discountContext->addLineItem($discountLineItem);
        }

        return $discountContext;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($sourceEntity): bool
    {
        return $sourceEntity instanceof ShoppingList;
    }
}
