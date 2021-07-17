<?php

namespace Oro\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;

class PriceUnitVisibilityProvider
{
    /**
     * @var UnitVisibilityInterface
     */
    private $unitVisibility;

    public function __construct(UnitVisibilityInterface $unitVisibility)
    {
        $this->unitVisibility = $unitVisibility;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isPriceUnitsVisibleByProduct(Product $product)
    {
        return $product->getUnitPrecisions()->filter(function (ProductUnitPrecision $unitPrecision) {
            return $unitPrecision->isSell()
                && $this->unitVisibility->isUnitCodeVisible($unitPrecision->getUnit()->getCode());
        })->count() > 0;
    }

    /**
     * @param Product[] $products
     * @return array
     */
    public function getPriceUnitsVisibilityByProducts(array $products)
    {
        return array_reduce($products, function ($result, Product $product) {
            $result[$product->getId()] = $this->isPriceUnitsVisibleByProduct($product);
            return $result;
        }, []);
    }
}
