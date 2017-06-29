<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
use Oro\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class LineItemsToDiscountLineItemsConverter
{
    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $productPricesDataProvider;

    /**
     * @param FrontendProductPricesDataProvider $productPricesDataProvider
     */
    public function __construct(FrontendProductPricesDataProvider $productPricesDataProvider)
    {
        $this->productPricesDataProvider = $productPricesDataProvider;
    }

    /**
     * @param LineItem[]|array $lineItems
     * @return array
     */
    public function convert(array $lineItems): array
    {
        $shoppingListPrices = $this->productPricesDataProvider->getProductsMatchedPrice($lineItems);
        $discountLineItems = [];
        foreach ($lineItems as $lineItem) {
            $discountLineItem = new DiscountLineItem();

            $unitCode = $lineItem->getProductUnitCode();

            $price = null;
            if (isset($shoppingListPrices[$lineItem->getProduct()->getId()][$unitCode])) {
                /** @var Price $price */
                $price = $shoppingListPrices[$lineItem->getProduct()->getId()][$unitCode];
                $discountLineItem->setPrice($price);
                $discountLineItem->setSubtotal($price->getValue() * $lineItem->getQuantity());
            } else {
                $discountLineItem->setSubtotal(0);
            }

            $discountLineItem->setQuantity($lineItem->getQuantity());
            $discountLineItem->setProduct($lineItem->getProduct());
            $discountLineItem->setProductUnit($lineItem->getProductUnit());
            $discountLineItem->setSourceLineItem($lineItem);

            $discountLineItems[] = $discountLineItem;
        }

        return $discountLineItems;
    }
}
