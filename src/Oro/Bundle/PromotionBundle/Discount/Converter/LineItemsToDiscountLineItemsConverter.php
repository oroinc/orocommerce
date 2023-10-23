<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Oro\Bundle\PromotionBundle\Discount\DiscountLineItem;
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

    private ?ProductLineItemPriceProviderInterface $productLineItemsPriceProvider = null;

    public function __construct(FrontendProductPricesDataProvider $productPricesDataProvider)
    {
        $this->productPricesDataProvider = $productPricesDataProvider;
    }

    public function setProductLineItemsPriceProvider(
        ?ProductLineItemPriceProviderInterface $productLineItemsPriceProvider
    ): void {
        $this->productLineItemsPriceProvider = $productLineItemsPriceProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(array $lineItems): array
    {
        if ($this->productLineItemsPriceProvider === null) {
            // BC fallback.
            return $this->doConvert($lineItems);
        }

        $discountLineItems = [];
        $lineItemsPrices = $this->productLineItemsPriceProvider->getProductLineItemsPrices($lineItems);

        /** @var LineItem[] $lineItems */
        foreach ($lineItems as $key => $lineItem) {
            $discountLineItem = $this->createDiscountLineItem($lineItem);
            if (!$discountLineItem) {
                continue;
            }

            if (isset($lineItemsPrices[$key])) {
                $discountLineItem->setPrice($lineItemsPrices[$key]->getPrice());
                // ProductLineItemPrice::getSubtotal is not used here on purpose because discount line item subtotal
                // must not be rounded - as discounts must be applied before rounding.
                $discountLineItem->setSubtotal(
                    $lineItemsPrices[$key]->getPrice()->getValue() * $lineItem->getQuantity()
                );
            } else {
                $discountLineItem->setSubtotal(0);
            }
            $discountLineItems[] = $discountLineItem;
        }

        return $discountLineItems;
    }

    /**
     * @param array<ProductLineItemInterface> $lineItems
     * @return array<DiscountLineItem>
     */
    private function doConvert(array $lineItems): array
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
