<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Provides information whether matrix for is available for products.
 */
class ProductMatrixAvailabilityProvider
{
    const MATRIX_AVAILABILITY_COUNT = 2;

    /** @var ProductVariantAvailabilityProvider */
    private $variantAvailability;

    /** @var ProductSearchRepository */
    private $productSearchRepository;

    /** @var array */
    private $cache;

    public function __construct(
        ProductVariantAvailabilityProvider $variantAvailability,
        ProductSearchRepository $productSearchRepository
    ) {
        $this->variantAvailability = $variantAvailability;
        $this->productSearchRepository = $productSearchRepository;
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
            $simpleProducts = $this->variantAvailability->getSimpleProductIdsByVariantFieldsGroupedByConfigurable(
                $configurableProductIds
            );
            $simpleProductsIds = array_unique(array_merge(...$simpleProducts));
            $simpleProductsData = $this->loadSimpleProducts($simpleProductsIds);
            foreach ($configurableProductIds as $configurableProductId) {
                if (empty($simpleProducts[$configurableProductId])) {
                    $result[$configurableProductId] = false;
                } else {
                    $isUnitSupportedBySimpleProducts = true;
                    [$configurableUnit] = $configurableProductData[$configurableProductId];
                    foreach ($simpleProducts[$configurableProductId] as $simpleProductId) {
                        if (!isset($simpleProductsData[$simpleProductId]['product_units'][$configurableUnit])) {
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
     * @param int[] $productIds
     *
     * @return array [product id => product data, ...]
     */
    private function loadSimpleProducts(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }

        $items = $this->productSearchRepository->createQuery()
            ->addSelect('product_id', Query::TYPE_INTEGER)
            ->addSelect('product_units', Query::TYPE_TEXT)
            ->addWhere(Criteria::expr()->in('integer.product_id', $productIds))
            ->addWhere(Criteria::expr()->eq('integer.is_variant', 1))
            ->setMaxResults(-1)
            ->execute();

        $result = [];
        foreach ($items as $item) {
            $itemData = $item->getSelectedData();
            $productId = $itemData['product_id'];
            $productUnits = $itemData['product_units'];
            $deserializedProductUnits = $productUnits
                ? unserialize($productUnits, ['allowed_classes' => false])
                : [];
            $itemData['product_units'] = $deserializedProductUnits;
            $result[$productId] = $itemData;
        }

        return $result;
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
