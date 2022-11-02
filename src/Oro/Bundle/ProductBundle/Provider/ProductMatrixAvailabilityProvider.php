<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;

/**
 * Provides information whether matrix form is available for products.
 */
class ProductMatrixAvailabilityProvider
{
    private const MATRIX_AVAILABILITY_COUNT = 2;

    private ProductVariantAvailabilityProvider $variantAvailability;
    private FrontendProductUnitsProvider $productUnitsProvider;

    private array $matrixFormAvailability = [];

    public function __construct(
        ProductVariantAvailabilityProvider $variantAvailability,
        FrontendProductUnitsProvider $productUnitsProvider
    ) {
        $this->variantAvailability = $variantAvailability;
        $this->productUnitsProvider = $productUnitsProvider;
    }

    public function isMatrixFormAvailable(Product $product): bool
    {
        if ($product->isSimple()) {
            return false;
        }

        $productId = $product->getId();
        if (isset($this->matrixFormAvailability[$productId])) {
            return $this->matrixFormAvailability[$productId];
        }

        $availability = $this->getMatrixAvailability($product);
        $this->matrixFormAvailability[$productId] = $availability;

        return $availability;
    }

    /**
     * @param Product[] $products
     *
     * @return Product[] [product id => product, ...]
     */
    public function isMatrixFormAvailableForProducts(array $products): array
    {
        $isMatrixFormAvailableProducts = [];
        foreach ($products as $product) {
            if ($product->isConfigurable()) {
                $productId = $product->getId();
                if ($this->isVariantsCountAcceptable(count($product->getVariantFields()))) {
                    $isMatrixFormAvailableProducts[$productId] = $product;
                }
            }
        }

        return $isMatrixFormAvailableProducts;
    }

    /**
     * @param array $configurableProductData [configurable product id => [product unit, variant fields count], ...]
     *
     * @return array [configurable product id => is matrix form available, ...]
     */
    public function getMatrixAvailabilityByConfigurableProductData(array $configurableProductData): array
    {
        $result = [];

        $configurableProductIds = [];
        foreach ($configurableProductData as $configurableProductId => [, $variantFieldsCount]) {
            if ($this->isVariantsCountAcceptable($variantFieldsCount)) {
                $configurableProductIds[] = $configurableProductId;
            } else {
                $result[$configurableProductId] = false;
            }
        }
        if ($configurableProductIds) {
            $simpleProducts = $this->getSimpleProducts($configurableProductIds);
            $simpleProductUnits = $this->getSimpleProductUnits($simpleProducts);
            foreach ($configurableProductIds as $configurableProductId) {
                if (empty($simpleProducts[$configurableProductId])) {
                    $result[$configurableProductId] = false;
                } else {
                    $isUnitSupportedBySimpleProducts = true;
                    [$configurableUnit] = $configurableProductData[$configurableProductId];
                    foreach ($simpleProducts[$configurableProductId] as $simpleProductId) {
                        if (!isset($simpleProductUnits[$simpleProductId])
                            || !\in_array($configurableUnit, $simpleProductUnits[$simpleProductId], true)
                        ) {
                            $isUnitSupportedBySimpleProducts = false;
                            break;
                        }
                    }
                    $result[$configurableProductId] = $isUnitSupportedBySimpleProducts;
                }
            }
        }

        return $result;
    }

    /**
     * @param int[] $configurableProductIds
     *
     * @return array [configurable product id => [simple product id, ...], ...]
     */
    private function getSimpleProducts(array $configurableProductIds): array
    {
        return $this->variantAvailability->getSimpleProductIdsByVariantFieldsGroupedByConfigurable(
            $configurableProductIds
        );
    }

    /**
     * @param array $simpleProducts [configurable product id => [simple product id, ...], ...]
     *
     * @return array [product id => [unit code, ...], ...]
     */
    private function getSimpleProductUnits(array $simpleProducts): array
    {
        $simpleProductIds = array_unique(array_merge(...$simpleProducts));

        return $simpleProductIds
            ? $this->productUnitsProvider->getUnitsForProducts($simpleProductIds)
            : [];
    }

    private function getMatrixAvailability(Product $product): bool
    {
        if (!$this->isVariantsCountAcceptable(count($product->getVariantFields()))) {
            return false;
        }

        $simpleProducts = $this->variantAvailability->getSimpleProductsByVariantFields($product);

        return $this->isUnitSupportedBySimpleProducts($product, $simpleProducts);
    }

    private function isVariantsCountAcceptable(int $variantFieldsCount): bool
    {
        return $variantFieldsCount && $variantFieldsCount <= self::MATRIX_AVAILABILITY_COUNT;
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

    private function isProductSupportsUnit(Product $product, ProductUnit $unit): bool
    {
        $productUnits = $product->getUnitPrecisions()->map(
            function (ProductUnitPrecision $unitPrecision) {
                return $unitPrecision->getUnit();
            }
        );

        return $productUnits->contains($unit);
    }
}
