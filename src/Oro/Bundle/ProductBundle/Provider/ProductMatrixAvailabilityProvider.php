<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductMatrixAvailabilityProvider
{
    const MATRIX_AVAILABILITY_COUNT = 2;

    /** @var ProductVariantAvailabilityProvider */
    private $variantAvailability;

    /** @var array */
    private $cache;

    /**
     * @param ProductVariantAvailabilityProvider $variantAvailability
     */
    public function __construct(
        ProductVariantAvailabilityProvider $variantAvailability
    ) {
        $this->variantAvailability = $variantAvailability;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isMatrixFormAvailable(Product $product)
    {
        if ($product->isSimple()) {
            return false;
        }

        if (isset($this->cache[$product->getId()])) {
            return $this->cache[$product->getId()];
        }

        $availability = $this->getMatrixAvailability($product);
        $this->cache[$product->getId()] = $availability;

        return $availability;
    }

    /**
     * @param Product $product
     * @return bool
     */
    protected function getMatrixAvailability(Product $product)
    {
        $variants = $this->variantAvailability->getVariantFieldsAvailability($product);

        $variantsCount = count($variants);
        if ($variantsCount === 0 || $variantsCount > self::MATRIX_AVAILABILITY_COUNT) {
            return false;
        }

        $simpleProducts = $this->variantAvailability->getSimpleProductsByVariantFields($product);
        if (!$simpleProducts) {
            return false;
        }

        $configurableUnit = $product->getPrimaryUnitPrecision()->getUnit();

        foreach ($simpleProducts as $simpleProduct) {
            if (!$this->isProductSupportsUnit($simpleProduct, $configurableUnit)) {
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
    private function isProductSupportsUnit(Product $product, ProductUnit $unit)
    {
        $productUnits = $product->getUnitPrecisions()->map(
            function (ProductUnitPrecision $unitPrecision) {
                return $unitPrecision->getUnit();
            }
        );

        return $productUnits->contains($unit);
    }
}
