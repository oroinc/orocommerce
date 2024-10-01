<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\Factory;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\Factory\ProductLineItemPriceFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductLineItemPrice\ProductKitLineItemPrice;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitPriceDTO;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Creates a product kit line item price from a product line item and a product price.
 */
class ProductKitLineItemPriceFactory implements ProductLineItemPriceFactoryInterface
{
    private ProductLineItemPriceFactoryInterface $productLineItemPriceFactory;

    private RoundingServiceInterface $roundingService;

    public function __construct(
        ProductLineItemPriceFactoryInterface $productLineItemPriceFactory,
        RoundingServiceInterface $roundingService
    ) {
        $this->productLineItemPriceFactory = $productLineItemPriceFactory;
        $this->roundingService = $roundingService;
    }

    #[\Override]
    public function createForProductLineItem(
        ProductLineItemInterface|ProductKitItemLineItemsAwareInterface $lineItem,
        ProductPriceInterface|ProductKitPriceDTO $productPrice
    ): ?ProductLineItemPrice {
        if (!$this->isSupported($lineItem, $productPrice)) {
            return null;
        }

        $kitItemLineItemsPrices = [];
        foreach ($lineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItem = $kitItemLineItem->getKitItem();
            if ($kitItem === null) {
                continue;
            }

            $kitItemPrice = $productPrice->getKitItemPrice($kitItem);
            if ($kitItemPrice === null) {
                if ($kitItem->isOptional()) {
                    // Optional product kit item does not have a price, but product kit line item price
                    // still can be calculated.
                    continue;
                }

                // Required product kit item does not have a price, so product kit line item price
                // cannot be calculated as well.
                return null;
            }

            $kitItemLineItemPrice = $this->productLineItemPriceFactory
                ->createForProductLineItem($kitItemLineItem, $kitItemPrice);
            if ($kitItemLineItemPrice !== null) {
                $kitItemLineItemsPrices[] = $kitItemLineItemPrice;
            }
        }

        $kitLineItemPrice = $productPrice->getPrice();
        $kitLineItemSubtotal = BigDecimal::of($kitLineItemPrice->getValue())
            ->multipliedBy((float)$lineItem->getQuantity())
            ->toFloat();
        $kitLineItemSubtotal = $this->roundingService->round($kitLineItemSubtotal);

        $productKitLineItemPrice = new ProductKitLineItemPrice($lineItem, $kitLineItemPrice, $kitLineItemSubtotal);
        foreach ($kitItemLineItemsPrices as $kitItemLineItemPrice) {
            $productKitLineItemPrice->addKitItemLineItemPrice($kitItemLineItemPrice);
        }

        return $productKitLineItemPrice;
    }

    #[\Override]
    public function isSupported(
        ProductLineItemInterface|ProductKitItemLineItemsAwareInterface $lineItem,
        ProductPriceInterface|ProductKitPriceDTO $productPrice
    ): bool {
        return $lineItem instanceof ProductKitItemLineItemsAwareInterface
            && $productPrice instanceof ProductKitPriceDTO;
    }
}
