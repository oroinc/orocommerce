<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Provider;

use Brick\Math\BigDecimal;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProviderInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemInterface;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Provides available product prices for the specified product line item.
 * Takes into account product kit item line item price when calculating line item product price of a product kit.
 */
class ProductLineItemProductPriceProvider implements ProductLineItemProductPriceProviderInterface
{
    private ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory;

    private ProductPriceByMatchingCriteriaProviderInterface $productPriceByMatchingCriteriaProvider;

    private RoundingServiceInterface $roundingService;

    public function __construct(
        ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory,
        ProductPriceByMatchingCriteriaProviderInterface $productPriceByMatchingCriteriaProvider,
        RoundingServiceInterface $roundingService
    ) {
        $this->productPriceByMatchingCriteriaProvider = $productPriceByMatchingCriteriaProvider;
        $this->productPriceCriteriaFactory = $productPriceCriteriaFactory;
        $this->roundingService = $roundingService;
    }

    #[\Override]
    public function getProductLineItemProductPrices(
        ProductLineItemInterface $productLineItem,
        ProductPriceCollectionDTO $productPriceCollection,
        string $currency
    ): array {
        $product = $productLineItem->getProduct();
        if ($product === null) {
            return [];
        }

        $productPrices = new \AppendIterator();
        foreach ($product->getAvailableUnits() as $productUnit) {
            $productPrices->append(
                $productPriceCollection->getMatchingByCriteria($product->getId(), $productUnit->getCode(), $currency)
            );
        }

        if ($productLineItem instanceof ProductKitItemLineItemsAwareInterface && $product->isKit() === true) {
            $productPrices = $this->getProductPricesForKitLineItem(
                $productLineItem,
                $productPriceCollection,
                $productPrices,
                $currency
            );
        }

        return $productPrices instanceof \Traversable ? iterator_to_array($productPrices, false) : $productPrices;
    }

    /**
     * @param ProductLineItemInterface|ProductKitItemLineItemsAwareInterface $productLineItem
     * @param ProductPriceCollectionDTO $productPriceCollection
     * @param iterable<ProductPriceInterface> $productPrices
     * @param string $currency
     *
     * @return iterable<ProductPriceInterface>
     */
    private function getProductPricesForKitLineItem(
        ProductLineItemInterface|ProductKitItemLineItemsAwareInterface $productLineItem,
        ProductPriceCollectionDTO $productPriceCollection,
        iterable $productPrices,
        string $currency
    ): iterable {
        $product = $productLineItem->getProduct();

        $kitItemLineItemsPriceValue = BigDecimal::of(0.0);
        foreach ($productLineItem->getKitItemLineItems() as $kitItemLineItem) {
            $kitItemPrice = $this->getKitItemLineItemPrice($kitItemLineItem, $productPriceCollection);
            if ($kitItemPrice === null) {
                continue;
            }

            $kitItemPriceValue = BigDecimal::of($kitItemPrice->getValue())
                ->multipliedBy($kitItemLineItem->getQuantity());
            $kitItemPriceValue = $this->roundingService->round($kitItemPriceValue->toFloat());

            $kitItemLineItemsPriceValue = $kitItemLineItemsPriceValue->plus($kitItemPriceValue);
        }

        $productKitPrices = [];
        foreach ($productPrices as $productPrice) {
            $priceValue = BigDecimal::of($productPrice->getPrice()->getValue())
                ->plus($kitItemLineItemsPriceValue);

            $productUnit = $productPrice->getUnit();
            $productKitPrices[$productUnit->getCode()][] = new ProductPriceDTO(
                $product,
                Price::create($priceValue->toFloat(), $currency),
                $productPrice->getQuantity(),
                $productUnit
            );
        }

        foreach ($product->getAvailableUnits() as $productUnit) {
            if (empty($productKitPrices[$productUnit->getCode()])) {
                $productKitPrices[$productUnit->getCode()][] = new ProductPriceDTO(
                    $product,
                    Price::create($kitItemLineItemsPriceValue->toFloat(), $currency),
                    1.0,
                    $productUnit
                );
            }
        }

        return array_merge(...array_values($productKitPrices));
    }

    private function getKitItemLineItemPrice(
        ProductKitItemLineItemInterface|PriceAwareInterface $kitItemLineItem,
        ProductPriceCollectionDTO $productPriceCollection
    ): ?Price {
        $price = null;
        if ($kitItemLineItem instanceof PriceAwareInterface && $kitItemLineItem->getPrice() !== null) {
            $price = $kitItemLineItem->getPrice();
        } elseif ($kitItemLineItem->getProduct() && $kitItemLineItem->getProductUnit()) {
            $productPriceCriteria = $this->productPriceCriteriaFactory->createFromProductLineItem($kitItemLineItem);
            if ($productPriceCriteria !== null) {
                $price = $this->productPriceByMatchingCriteriaProvider
                    ->getProductPriceMatchingCriteria($productPriceCriteria, $productPriceCollection)
                    ?->getPrice();
            }
        }

        return $price;
    }
}
