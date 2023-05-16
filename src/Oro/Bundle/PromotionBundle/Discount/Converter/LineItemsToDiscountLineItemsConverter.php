<?php

namespace Oro\Bundle\PromotionBundle\Discount\Converter;

use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Converts shopping list LineItem entities collection to the collection of DiscountLineItem models.
 */
class LineItemsToDiscountLineItemsConverter extends AbstractLineItemsToDiscountLineItemsConverter
{
    private ProductLineItemPriceProviderInterface $productLineItemsPriceProvider;

    public function __construct(ProductLineItemPriceProviderInterface $productLineItemsPriceProvider)
    {
        $this->productLineItemsPriceProvider = $productLineItemsPriceProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(array $lineItems): array
    {
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
                $discountLineItem->setSubtotal($lineItemsPrices[$key]->getSubtotal());
            } else {
                $discountLineItem->setSubtotal(0);
            }
            $discountLineItems[] = $discountLineItem;
        }

        return $discountLineItems;
    }
}
