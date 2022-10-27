<?php

namespace Oro\Bundle\ProductBundle\Search;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\WebsiteSearchBundle\Attribute\Type\SearchAttributeTypeInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexDataProvider;

/**
 * Adds information from all product variants to the main configurable product
 */
class ProductVariantIndexDataProviderDecorator implements ProductIndexDataProviderInterface
{
    private ProductIndexDataProviderInterface $originalProvider;
    private ManagerRegistry $doctrine;

    public function __construct(
        ProductIndexDataProviderInterface $originalProvider,
        ManagerRegistry $doctrine
    ) {
        $this->originalProvider = $originalProvider;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexData(Product $product, FieldConfigModel $attribute, array $localizations): \ArrayIterator
    {
        $data = $this->originalProvider->getIndexData($product, $attribute, $localizations);
        $this->buildIndexData($product, $attribute, $localizations, $data);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    private function buildIndexData(
        Product $product,
        FieldConfigModel $attribute,
        array $localizations,
        \ArrayIterator $data
    ): void {
        /** @var ProductRepository $productRepository */
        $productRepository = $this->doctrine->getRepository(Product::class);
        if ($product->getType() === Product::TYPE_CONFIGURABLE) {
            foreach ($productRepository->getVariantsLinksProducts($product) as $variantProduct) {
                if ($variantProduct->getType() === Product::TYPE_SIMPLE) {
                    // It means that we are indexing the simple products in scope of configurable product data and
                    // the separate product data for simple product as well.
                    // It's required to prevent excess re-indexation when value of "Display Simple Variations" option
                    // in the "System Configuration" become "Everywhere".
                    $variantData = $this->originalProvider->getIndexData($variantProduct, $attribute, $localizations);
                    if (\in_array($attribute->getType(), ['enum', 'multiEnum'], true)) {
                        $searchableName = $this->getSearchableName($attribute);
                        $this->addEnumVariantData($data, $variantData, $attribute, $searchableName);
                        $this->mergeSearchable($data, $searchableName);
                    }
                    $this->addAllTextVariantData($data, $variantData);
                }
            }
        }
    }

    private function addEnumVariantData(
        \ArrayIterator $data,
        \ArrayIterator $variantData,
        FieldConfigModel $attribute,
        string $searchableName
    ): void {
        foreach ($variantData as $variantModel) {
            $variantFieldName = $variantModel->getFieldName();
            // if field value model (not all_text model)
            if (str_starts_with($variantFieldName, $attribute->getFieldName() . '_')) {
                // collect searchable enum values
                if ($variantFieldName === $searchableName && $variantModel->getValue()) {
                    $data->append($variantModel);
                    continue;
                }

                $isVariantOptionMissing = true;
                foreach ($data as $productModel) {
                    // if product already has option from the variant
                    if ($productModel->getFieldName() === $variantFieldName) {
                        $isVariantOptionMissing = false;
                        break;
                    }
                }
                // add missing option from the variant
                if ($isVariantOptionMissing) {
                    $data->append($variantModel);
                }
            }
        }
    }

    /**
     * Merge searchable data into one field.
     */
    private function mergeSearchable(\ArrayIterator $data, string $searchableName): void
    {
        $combinedValues = [];
        foreach ($data as $key => $productModel) {
            if ($productModel->getFieldName() === $searchableName) {
                $combinedValues[] = $productModel->getValue();
                $data->offsetUnset($key);
            }
        }

        if ($combinedValues) {
            $productModel = new ProductIndexDataModel(
                $searchableName,
                implode(' ', array_unique(explode(' ', implode(' ', $combinedValues)))),
                [],
                false,
                false
            );
            $data->append($productModel);
        }
    }

    private function addAllTextVariantData(
        \ArrayIterator $data,
        \ArrayIterator $variantData
    ): void {
        foreach ($variantData as $variantModel) {
            // add all_text fields from variants to main product
            if ($variantModel->getFieldName() === IndexDataProvider::ALL_TEXT_L10N_FIELD) {
                $data->append($variantModel);
            }
        }
    }

    private function getSearchableName(FieldConfigModel $attribute): string
    {
        return sprintf('%s_%s', $attribute->getFieldName(), SearchAttributeTypeInterface::SEARCHABLE_SUFFIX);
    }
}
