<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Converts shopping list LineItem entities collection to the collection of DiscountLineItem models.
 */
class LineItemsToDiscountLineItemsConverter extends AbstractLineItemsToDiscountLineItemsConverter
{
    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $productPricesDataProvider;

    public function __construct(FrontendProductPricesDataProvider $productPricesDataProvider)
    {
        $this->productPricesDataProvider = $productPricesDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(array $lineItems): array
    {
        $discountLineItems = [];
        $shoppingListPrices = $this->productPricesDataProvider->getProductsMatchedPrice($lineItems);

        /** @var LineItem[] $lineItems */
        foreach ($lineItems as $lineItem) {
            $discountLineItem = $this->createDiscountLineItem($lineItem);
            if (!$discountLineItem) {
                continue;
            }

            $productId = $lineItem->getProduct()->getId();
            $unitCode = $lineItem->getProductUnitCode();
            $price = null;
            if (isset($shoppingListPrices[$productId][$unitCode])) {
                /** @var Price $price */
                $price = $shoppingListPrices[$productId][$unitCode];
                $discountLineItem->setPrice($price);
                $discountLineItem->setSubtotal($price->getValue() * $lineItem->getQuantity());
            } else {
                $discountLineItem->setSubtotal(0);
            }
            $discountLineItems[] = $discountLineItem;
        }

        return $discountLineItems;
    }
}
