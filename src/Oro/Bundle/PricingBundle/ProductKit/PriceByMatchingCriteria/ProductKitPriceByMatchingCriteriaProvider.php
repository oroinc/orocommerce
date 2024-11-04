<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\PriceByMatchingCriteria;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitItemPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitPriceDTO;
use Oro\Bundle\PricingBundle\ProductKit\ProductPrice\ProductKitPriceInterface;
use Oro\Bundle\PricingBundle\ProductKit\ProductPriceCriteria\ProductKitPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProviderInterface;
use Oro\Component\Math\BigDecimal;

/**
 * Provides a product kit price matching the specified product price criteria.
 */
class ProductKitPriceByMatchingCriteriaProvider implements ProductPriceByMatchingCriteriaProviderInterface
{
    private ProductPriceByMatchingCriteriaProviderInterface $simpleProductPriceByMatchingCriteriaProvider;

    private RoundingServiceInterface $roundingService;

    public function __construct(
        ProductPriceByMatchingCriteriaProviderInterface $simpleProductPriceByMatchingCriteriaProvider,
        RoundingServiceInterface $roundingService
    ) {
        $this->simpleProductPriceByMatchingCriteriaProvider = $simpleProductPriceByMatchingCriteriaProvider;
        $this->roundingService = $roundingService;
    }

    #[\Override]
    public function getProductPriceMatchingCriteria(
        ProductPriceCriteria|ProductKitPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): ?ProductKitPriceInterface {
        if (!$this->isSupported($productPriceCriteria, $productPriceCollection)) {
            return null;
        }

        $productPrice = $this->simpleProductPriceByMatchingCriteriaProvider
            ->getProductPriceMatchingCriteria($productPriceCriteria, $productPriceCollection);
        $productKitPriceValue = BigDecimal::of($productPrice?->getPrice()->getValue() ?? 0.0);
        $productKitItemPrices = [];

        foreach ($productPriceCriteria->getKitItemsProductsPriceCriteria() as $kitItemProductPriceCriterion) {
            $eachProductPrice = $this->simpleProductPriceByMatchingCriteriaProvider
                ->getProductPriceMatchingCriteria($kitItemProductPriceCriterion, $productPriceCollection);
            if ($eachProductPrice === null) {
                if ($kitItemProductPriceCriterion->getKitItem()->isOptional()) {
                    // Optional product kit item does not have a price, but product kit price still can be calculated.
                    continue;
                }

                // Required product kit item does not have a price, so product kit price cannot be calculated as well.
                return null;
            }

            $productKitItemPriceValue = BigDecimal::of($eachProductPrice->getPrice()->getValue() ?? 0.0);
            $productKitItemPriceValue = $productKitItemPriceValue
                ->multipliedBy($kitItemProductPriceCriterion->getQuantity());
            $productKitItemPriceValue = $this->roundingService->round($productKitItemPriceValue->toFloat());

            $productKitPriceValue = $productKitPriceValue->plus($productKitItemPriceValue);

            $productKitItemPrices[] = new ProductKitItemPriceDTO(
                $kitItemProductPriceCriterion->getKitItem(),
                $eachProductPrice->getProduct(),
                $eachProductPrice->getPrice(),
                $eachProductPrice->getQuantity(),
                $eachProductPrice->getUnit()
            );
        }

        $productKitPrice = new ProductKitPriceDTO(
            $productPriceCriteria->getProduct(),
            Price::create($productKitPriceValue->toFloat(), $productPriceCriteria->getCurrency()),
            $productPrice?->getQuantity() ?? 1.0,
            $productPriceCriteria->getProductUnit()
        );

        foreach ($productKitItemPrices as $productKitItemPrice) {
            $productKitPrice->addKitItemPrice($productKitItemPrice);
        }

        return $productKitPrice;
    }

    #[\Override]
    public function isSupported(
        ProductPriceCriteria|ProductKitPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): bool {
        return $productPriceCriteria instanceof ProductKitPriceCriteria;
    }
}
