<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates a product line item price from a product line item and a product price.
 */
class SimpleProductLineItemPriceFactory implements ProductLineItemPriceFactoryInterface
{
    private RoundingServiceInterface $roundingService;

    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    #[\Override]
    public function createForProductLineItem(
        ProductLineItemInterface $lineItem,
        ProductPriceInterface $productPrice
    ): ProductLineItemPrice {
        $price = $productPrice->getPrice();
        $subtotalValue = BigDecimal::of($price->getValue())
            ->multipliedBy((float)$lineItem->getQuantity())
            ->toFloat();
        $subtotalValue = $this->roundingService->round($subtotalValue);

        return new ProductLineItemPrice($lineItem, $price, $subtotalValue);
    }

    #[\Override]
    public function isSupported(ProductLineItemInterface $lineItem, ProductPriceInterface $productPrice): bool
    {
        return true;
    }
}
