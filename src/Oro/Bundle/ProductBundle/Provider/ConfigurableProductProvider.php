<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;
use Oro\Bundle\ProductBundle\ProductVariant\VariantFieldValueHandler\EnumVariantFieldValueHandler;

class ConfigurableProductProvider
{
    /**
     * @var CustomFieldProvider
     */
    protected $customFieldProvider;

    /**
     * @var ProductVariantAvailabilityProvider
     */
    protected $productVariantAvailabilityProvider;

    /**
     * @var ProductVariantFieldValueHandlerRegistry
     */
    protected $fieldValueHandlerRegistry;

    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param CustomFieldProvider $customFieldProvider
     * @param ProductVariantAvailabilityProvider $productVariantAvailabilityProvider
     * @param PropertyAccessor $propertyAccessor
     * @param ProductVariantFieldValueHandlerRegistry $fieldValueHandlerRegistry
     */
    public function __construct(
        CustomFieldProvider $customFieldProvider,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        PropertyAccessor $propertyAccessor,
        ProductVariantFieldValueHandlerRegistry $fieldValueHandlerRegistry
    ) {
        $this->customFieldProvider = $customFieldProvider;
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->fieldValueHandlerRegistry = $fieldValueHandlerRegistry;
    }

    /**
     * @param $lineItems
     * @return array|null
     */
    public function getProducts($lineItems)
    {
        if (!$lineItems) {
            return null;
        }

        $variantFieldNames = [];
        foreach ($lineItems as $key => $value) {
            $variantFieldNames += $this->getLineItemProduct($value);
        }

        return $variantFieldNames;
    }

    /**
     * @param $lineItem
     * @return array
     */
    public function getLineItemProduct($lineItem)
    {
        $customFields = $this->customFieldProvider->getEntityCustomFields(Product::class);
        $variantFieldNames = [];
        if ($lineItem instanceof ProductHolderInterface) {
            $parentProduct = $lineItem->getParentProduct();
            if (!$parentProduct) {
                return [];
            }
            $variantFields = $parentProduct->getVariantFields();
            $simpleProduct = $lineItem->getProduct();
            $variantFieldNames[$simpleProduct->getId()] = $this->getVariantFields(
                $simpleProduct,
                $variantFields,
                $customFields
            );
        }

        return $variantFieldNames;
    }

    /**
     * @param Product $product
     * @param $variantFields
     * @param $customFields
     * @return array
     */
    private function getVariantFields(Product $product, $variantFields, $customFields)
    {
        $fields = [];
        foreach ($variantFields as $key => $fieldName) {
            $fieldValue = $this->propertyAccessor->getValue($product, $fieldName);
            if ($fieldValue === null) {
                continue;
            }
            $fields[$fieldName] = $this->prepareFieldByType(
                $customFields[$fieldName]['type'],
                $fieldName,
                $fieldValue,
                $customFields[$fieldName]['label']
            );
        }

        return $fields;
    }

    /**
     * @param string $type
     * @param string $fieldName
     * @param mixed $fieldValue
     * @param string $label
     * @return array
     */
    private function prepareFieldByType($type, $fieldName, $fieldValue, $label)
    {
        $handler = $this->fieldValueHandlerRegistry->getVariantFieldValueHandler($type);

        $value = $handler->getHumanReadableValue($fieldName, $fieldValue);

        return [
            'value' => $value,
            'label' => $label,
            'type' => $type,
        ];
    }
}
