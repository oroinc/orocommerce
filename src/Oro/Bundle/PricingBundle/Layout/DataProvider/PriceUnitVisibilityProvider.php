<?php

namespace Oro\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

/**
 * Provides information about product unit visibility.
 */
class PriceUnitVisibilityProvider
{
    private UnitVisibilityInterface $unitVisibility;

    public function __construct(UnitVisibilityInterface $unitVisibility)
    {
        $this->unitVisibility = $unitVisibility;
    }

    public function isPriceUnitsVisibleByProduct(Product|ProductView $product): bool
    {
        if ($product instanceof ProductView) {
            return $this->isPriceUnitsVisibleByProductView($product);
        }

        $hasVisibleUnit = false;
        foreach ($product->getUnitPrecisions() as $unitPrecision) {
            if ($unitPrecision->isSell()
                && $this->unitVisibility->isUnitCodeVisible($unitPrecision->getUnit()->getCode())
            ) {
                $hasVisibleUnit = true;
                break;
            }
        }

        return $hasVisibleUnit;
    }

    /**
     * @param ProductView[] $products
     *
     * @return array [product id => a flag indicates whether a product has at least one visible unit, ...]
     */
    public function getPriceUnitsVisibilityByProducts(array $products): array
    {
        $result = [];
        foreach ($products as $product) {
            $result[$product->getId()] = $this->isPriceUnitsVisibleByProductView($product);
        }

        return $result;
    }

    private function isPriceUnitsVisibleByProductView(ProductView $product): bool
    {
        $hasVisibleUnit = false;
        foreach ($product->get('product_units') as $unit => $precision) {
            if ($this->unitVisibility->isUnitCodeVisible($unit)) {
                $hasVisibleUnit = true;
                break;
            }
        }

        return $hasVisibleUnit;
    }
}
