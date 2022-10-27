<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductFormAvailabilityProvider;

/**
 * Provides a set of methods to check matrix form availability for configurable products for product lists.
 * @see \Oro\Bundle\ProductBundle\Layout\DataProvider\ProductViewFormAvailabilityProvider
 * @see \Oro\Bundle\ProductBundle\Provider\ProductFormAvailabilityProvider
 */
class ProductListFormAvailabilityProvider
{
    private const PRODUCT_VIEW = 'gallery-view';

    private ProductFormAvailabilityProvider $productFormAvailabilityProvider;

    public function __construct(ProductFormAvailabilityProvider $productFormAvailabilityProvider)
    {
        $this->productFormAvailabilityProvider = $productFormAvailabilityProvider;
    }

    public function getAvailableMatrixFormType(ProductView $product): string
    {
        if ($product->get('type') !== Product::TYPE_CONFIGURABLE) {
            return Configuration::MATRIX_FORM_NONE;
        }

        $productId = $product->getId();
        $matrixFormTypes = $this->getMatrixFormTypes([$productId => $this->getConfigurableProductData($product)]);

        return $matrixFormTypes[$productId] ?? Configuration::MATRIX_FORM_NONE;
    }

    /**
     * @param ProductView[] $products
     *
     * @return array [product id => matrix form type, ...]
     */
    public function getAvailableMatrixFormTypes(array $products): array
    {
        $configurableProductData = [];
        foreach ($products as $product) {
            if ($product->get('type') === Product::TYPE_CONFIGURABLE) {
                $configurableProductData[$product->getId()] = $this->getConfigurableProductData($product);
            }
        }
        $matrixFormTypes = $configurableProductData
            ? $this->getMatrixFormTypes($configurableProductData)
            : [];

        $result = [];
        foreach ($products as $product) {
            $productId = $product->getId();
            $result[$productId] = $matrixFormTypes[$productId] ?? Configuration::MATRIX_FORM_NONE;
        }

        return $result;
    }

    /**
     * @param array $configurableProductData [configurable product id => [product unit, variant fields count], ...]
     *
     * @return array [configurable product id => matrix form type, ...]
     */
    private function getMatrixFormTypes(array $configurableProductData): array
    {
        return $this->productFormAvailabilityProvider->getAvailableMatrixFormTypes(
            $configurableProductData,
            self::PRODUCT_VIEW
        );
    }

    private function getConfigurableProductData(ProductView $product): array
    {
        return [$product->get('unit'), $product->get('variant_fields_count')];
    }
}
