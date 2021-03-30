<?php

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Attribute\SearchableInformationProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;

/**
 * Adds information from all product variants to the main configurable product
 */
class ProductVariantIndexDataProviderDecorator implements ProductIndexDataProviderInterface
{
    /** @var ProductIndexDataProviderInterface */
    private $originalProvider;

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
                    // It means that we are indexing the simple products in scope of configurable product data and
                    // the separate product data for simple product as well.
                    // It's required to prevent excess re-indexation when value of "Display Simple Variations" option
                    // in the "System Configuration" become "Everywhere".
                    $variantData = $this->originalProvider->getIndexData($variantProduct, $attribute, $localizations);
                    $productData = $this->addEnumVariantData($productData, $variantData, $attribute);
                    $productData = $this->addAllTextVariantData($productData, $variantData);
                }
            }
        }

        return $productData;
    }

    /**
     * @param array|ProductIndexDataModel[] $productData
     * @param array|ProductIndexDataModel[] $variantData
     * @param FieldConfigModel $attribute
     * @return array|ProductIndexDataModel[]
     */
    private function addEnumVariantData(
        $productData,
        $variantData,
        FieldConfigModel $attribute
    ) {
        if (\in_array($attribute->getType(), ['enum', 'multiEnum'], true)) {
            $searchableName = $attribute->getFieldName() . '_' . SearchableInformationProvider::SEARCHABLE_PREFIX;

            foreach ($variantData as $variantModel) {
                $variantFieldName = $variantModel->getFieldName();
                // if field value model (not all_text model)
                if (strpos($variantFieldName, $attribute->getFieldName() . '_') === 0) {
                    // collect searchable enum values
                    if ($variantFieldName === $searchableName && $variantModel->getValue()) {
                        $productData[] = $variantModel;
                        continue;
                    }

                    $isVariantOptionMissing = true;
                    foreach ($productData as $productModel) {
                        // if product already has option from the variant
                        if ($productModel->getFieldName() === $variantFieldName) {
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

            $productData = $this->mergeSearchable($productData, $searchableName);
        }

        return $productData;
    }

    /**
     * Merge searchable data into one field.
     *
     * @param array|ProductIndexDataModel[] $productData
     * @param string                        $searchableName
     *
     * @return array|ProductIndexDataModel[]
     */
    private function mergeSearchable(array $productData, string $searchableName): array
    {
        $combinedValues = [];

        foreach ($productData as $key => $model) {
            if ($model->getFieldName() === $searchableName) {
                $combinedValues[] = $model->getValue();
                unset($productData[$key]);
            }
        }

        if ($combinedValues) {
            $productData[] = new ProductIndexDataModel(
                $searchableName,
                implode(' ', array_unique(explode(' ', implode(' ', $combinedValues)))),
                [],
                false,
                false
            );
        }

        return array_values($productData);
    }

    /**
     * @param array|ProductIndexDataModel[] $productData
     * @param array|ProductIndexDataModel[] $variantData
     * @return array|ProductIndexDataModel[]
     */
    private function addAllTextVariantData($productData, $variantData)
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
