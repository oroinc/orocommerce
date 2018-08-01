<?php

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;

class ProductVariantProviderDecorator implements ProductIndexDataProviderInterface
{
    /** @var ProductIndexDataProviderInterface */
    protected $originalProvider;

    /**
     * @param ProductIndexDataProviderInterface $originalProvider
     */
    public function __construct(ProductIndexDataProviderInterface $originalProvider)
    {
        $this->originalProvider = $originalProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexData(Product $product, FieldConfigModel $attribute, array $localizations)
    {
        $productData = $this->originalProvider->getIndexData($product, $attribute, $localizations);

        if ($product->getType() === Product::TYPE_CONFIGURABLE) {
            foreach ($product->getVariantLinks() as $link) {
                $variantProduct = $link->getProduct();
                if ($variantProduct->getType() === Product::TYPE_SIMPLE) {
                    $variantData = $this->originalProvider->getIndexData($variantProduct, $attribute, $localizations);
                    $productData = $this->addEnumVariantData($productData, $variantData, $attribute);
                    $productData = $this->addAllTextVariantData($productData, $variantData);
                }
            }
        }

        return $productData;
    }

    /**
     * @param $productData array|ProductIndexDataModel[]
     * @param $variantData array|ProductIndexDataModel[]
     * @param FieldConfigModel $attribute
     * @return array|ProductIndexDataModel[]
     */
    protected function addEnumVariantData(
        $productData,
        $variantData,
        FieldConfigModel $attribute
    ) {
        if (\in_array($attribute->getType(), ['enum', 'multiEnum'], true)) {
            foreach ($variantData as $variantModel) {
                // if field value model (not all_text model)
                if (strpos($variantModel->getFieldName(), $attribute->getFieldName()) === 0) {
                    $isVariantOptionMissing = true;
                    foreach ($productData as $productModel) {
                        // if product already has option from the variant
                        if ($productModel->getFieldName() === $variantModel->getFieldName()) {
                            $isVariantOptionMissing = false;
                            break;
                        }
                    }
                    // add missing option from the variant
                    if ($isVariantOptionMissing) {
                        $productData[] = $variantModel;
                    }
                }
            }
        }

        return $productData;
    }

    /**
     * @param $productData array|ProductIndexDataModel[]
     * @param $variantData array|ProductIndexDataModel[]
     * @return array|ProductIndexDataModel[]
     */
    protected function addAllTextVariantData($productData, $variantData)
    {
        foreach ($variantData as $variantModel) {
            // add all_text fields from variants to main product
            if (\in_array(
                $variantModel->getFieldName(),
                [IndexDataProvider::ALL_TEXT_L10N_FIELD, IndexDataProvider::ALL_TEXT_FIELD],
                true
            )) {
                $productData[] = $variantModel;
            }
        }

        return $productData;
    }
}
