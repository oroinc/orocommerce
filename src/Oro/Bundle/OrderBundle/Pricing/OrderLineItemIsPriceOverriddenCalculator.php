<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Pricing;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProvider;

/**
 * Determines whether the price set on an OrderLineItem differs from the matched tier price.
 */
class OrderLineItemIsPriceOverriddenCalculator
{
    public function __construct(
        private readonly ProductPriceByMatchingCriteriaProvider $priceByMatchingCriteriaProvider,
    ) {
    }

    /**
     * Returns true when the line item price differs from the matched listed tier price.
     *
     * @param OrderLineItem $orderLineItem
     * @param array<int, ProductPriceInterface> $tierPrices Flat list of tier prices for the line item's product.
     *
     * @return bool True when the line item price differs from the matched listed tier price.
     */
    public function isOverridden(OrderLineItem $orderLineItem, array $tierPrices): bool
    {
        if (!$tierPrices) {
            return false;
        }

        $product = $orderLineItem->getProduct();
        $productUnit = $orderLineItem->getProductUnit();
        $price = $orderLineItem->getPrice();

        if ($product === null || $productUnit === null || $price === null) {
            return false;
        }

        $quantity = ($orderLineItem->getQuantity() ?? 1.0);
        // Use the order's current currency (may differ from the price currency when currency was just changed).
        $currency = $orderLineItem->getOrder()?->getCurrency() ?? $price->getCurrency();

        $productPriceCriteria = new ProductPriceCriteria($product, $productUnit, $quantity, $currency);
        $productPriceCollection = new ProductPriceCollectionDTO($tierPrices);

        $matchedPrice = $this->priceByMatchingCriteriaProvider->getProductPriceMatchingCriteria(
            $productPriceCriteria,
            $productPriceCollection
        );

        if ($matchedPrice === null) {
            return false;
        }

        return abs($price->getValue() - $matchedPrice->getPrice()->getValue()) > 1e-6;
    }
}
