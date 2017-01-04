<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;

class MatrixGridOrderProvider
{
    /**
     * @var ProductVariantAvailabilityProvider
     */
    private $productVariantAvailability;

    /**
     * @param ProductVariantAvailabilityProvider $productVariantAvailability
     */
    public function __construct(ProductVariantAvailabilityProvider $productVariantAvailability)
    {
        $this->productVariantAvailability = $productVariantAvailability;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isAvailable(Product $product)
    {
        try {
            $variants = $this->productVariantAvailability->getVariantFieldsWithAvailability($product);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        reset($variants);
        $firstFieldVariants = array_filter(current($variants));

        if (count($variants) > 2 || count($firstFieldVariants) > 5) {
            return false;
        }

        $configurableProductPrimaryUnit = $product->getPrimaryUnitPrecision()->getUnit();
        $simpleProducts = $this->productVariantAvailability->getSimpleProductsByVariantFields($product);
        foreach ($simpleProducts as $simpleProduct) {
            if (!$this->doSimpleProductSupportsUnitPrecision($simpleProduct, $configurableProductPrimaryUnit)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Product $product
     * @param ProductUnit $unit
     * @return bool
     */
    private function doSimpleProductSupportsUnitPrecision(Product $product, ProductUnit $unit)
    {
        $productUnits = $product->getUnitPrecisions()->map(
            function (ProductUnitPrecision $unitPrecision) {
                return $unitPrecision->getUnit();
            }
        );

        return $productUnits->contains($unit);
    }
}
