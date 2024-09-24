<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;

/**
 * Provides a product price matching the specified product price criteria.
 */
class SimpleProductPriceByMatchingCriteriaProvider implements ProductPriceByMatchingCriteriaProviderInterface
{
    public function __construct(private ConfigManager $configManager)
    {
    }

    #[\Override]
    public function getProductPriceMatchingCriteria(
        ProductPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): ?ProductPriceInterface {
        $productPrices = $productPriceCollection->getMatchingByCriteria(
            $productPriceCriteria->getProduct()->getId(),
            $productPriceCriteria->getProductUnit()->getCode(),
            $productPriceCriteria->getCurrency()
        );

        if (!$productPrices->valid()) {
            return null;
        }

        $minimumQuantityProductPrice = $productPrices->current();
        $matchedProductPrice = null;
        $matchedQuantity = 0;

        foreach ($productPrices as $productPrice) {
            if ($matchedQuantity <= $productPriceCriteria->getQuantity()
                && $productPriceCriteria->getQuantity() >= $productPrice->getQuantity()) {
                $matchedQuantity = $productPrice->getQuantity();
                $matchedProductPrice = $productPrice;
            }
        }

        if ($matchedProductPrice === null) {
            $matchedProductPrice = (
                $this->isFractionalQuantityLessThenUnitPriceCalculation($productPriceCriteria) ||
                    $this->isFractionalQuantityLessThenMinimumPricedPriceCalculation($productPriceCriteria) ||
                    $this->isQuantityLessThenMinimumPricedPriceCalculation($productPriceCriteria)
            ) ? $minimumQuantityProductPrice : null;
        }

        return $matchedProductPrice;
    }

    private function isFractionalQuantityLessThenUnitPriceCalculation(ProductPriceCriteria $productPriceCriteria): bool
    {
        if ($this->configManager->get('oro_pricing.fractional_quantity_less_then_unit_price_calculation')
            && $productPriceCriteria->getQuantity() > 0 && $productPriceCriteria->getQuantity() < 1
            && $productPriceCriteria->getProduct()->getPrimaryUnitPrecision()->getPrecision() > 0
        ) {
            return true;
        }

        return false;
    }

    private function isFractionalQuantityLessThenMinimumPricedPriceCalculation(
        ProductPriceCriteria $productPriceCriteria
    ): bool {
        if ($this->configManager->get('oro_pricing.fractional_quantity_less_then_minimum_priced_price_calculation')
            && $productPriceCriteria->getProduct()->getPrimaryUnitPrecision()->getPrecision() > 0
        ) {
            return  true;
        }

        return false;
    }

    private function isQuantityLessThenMinimumPricedPriceCalculation(ProductPriceCriteria $productPriceCriteria): bool
    {
        if ($this->configManager->get('oro_pricing.quantity_less_then_minimum_priced_price_calculation')
            && $productPriceCriteria->getProduct()->getPrimaryUnitPrecision()->getPrecision() === 0
        ) {
            return true;
        }

        return false;
    }

    #[\Override]
    public function isSupported(
        ProductPriceCriteria $productPriceCriteria,
        ProductPriceCollectionDTO $productPriceCollection
    ): bool {
        return true;
    }
}
