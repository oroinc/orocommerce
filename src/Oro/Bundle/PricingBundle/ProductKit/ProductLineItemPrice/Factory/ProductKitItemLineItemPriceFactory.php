<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\Factory;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory\ProductLineItemPriceFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitItemLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitItemPriceDTO;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates a product kit item line item price for a product line item and a product price.
 */
class ProductKitItemLineItemPriceFactory implements ProductLineItemPriceFactoryInterface
{
    private RoundingServiceInterface $roundingService;

    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    #[\Override]
    public function createForProductLineItem(
        ProductLineItemInterface|ProductKitItemLineItemInterface $lineItem,
        ProductPriceInterface|ProductKitItemPriceDTO $productPrice
    ): ?ProductLineItemPrice {
        if (!$this->isSupported($lineItem, $productPrice)) {
            return null;
        }

        $price = $productPrice->getPrice();
        $subtotalValue = BigDecimal::of($price->getValue())
            ->multipliedBy((float)$lineItem->getQuantity())
            ->toFloat();
        $subtotalValue = $this->roundingService->round($subtotalValue);

        return new ProductKitItemLineItemPrice($lineItem, $price, $subtotalValue);
    }

    #[\Override]
    public function isSupported(
        ProductLineItemInterface|ProductKitItemLineItemInterface $lineItem,
        ProductPriceInterface|ProductKitItemPriceDTO $productPrice
    ): bool {
        return $lineItem instanceof ProductKitItemLineItemInterface
            && $productPrice instanceof ProductKitItemPriceDTO;
    }
}
