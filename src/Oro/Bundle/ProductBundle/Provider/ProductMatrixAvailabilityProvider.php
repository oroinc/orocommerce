<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Provides information whether matrix for is available for products.
 */
class ProductMatrixAvailabilityProvider
{
    const MATRIX_AVAILABILITY_COUNT = 2;

    /** @var ProductVariantAvailabilityProvider */
    private $variantAvailability;

    /** @var array */
    private $cache;

    public function __construct(ProductVariantAvailabilityProvider $variantAvailability)
    {
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
        if (!$this->isVariantsCountAcceptable($product)) {
            return false;
        }

        $simpleProducts = $this->variantAvailability->getSimpleProductsByVariantFields($product);

        return $this->isUnitSupportedBySimpleProducts($product, $simpleProducts);
    }

    private function isVariantsCountAcceptable(Product $configurableProduct): bool
    {
        $variantsCount = count($configurableProduct->getVariantFields());

        return $variantsCount && $variantsCount <= self::MATRIX_AVAILABILITY_COUNT;
    }

    /**
     * @param Product[] $products
     * @return array
     */
    public function isMatrixFormAvailableForProducts(array $products): array
    {
        $isMatrixFormAvailableProducts = [];
        foreach ($products as $product) {
            if ($product->isConfigurable()) {
                $productId = $product->getId();
                if ($this->isVariantsCountAcceptable($product)) {
                    $isMatrixFormAvailableProducts[$productId] = $product;
                }
            }
        }

        return $isMatrixFormAvailableProducts;
    }

    private function isUnitSupportedBySimpleProducts(Product $configurableProduct, array $simpleProducts): bool
    {
        if (!$simpleProducts) {
            return false;
        }

        $configurableUnit = $configurableProduct->getPrimaryUnitPrecision()->getUnit();

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
