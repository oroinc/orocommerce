<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;

use Symfony\Component\PropertyAccess\PropertyAccessor;

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
     * @param ProductHolderInterface|mixed $lineItem
     * @return array
     */
    public function getLineItemProduct($lineItem)
    {
        $customFields = $this->customFieldProvider->getEntityCustomFields(Product::class);
        $variantFieldNames = [];
        if (method_exists($lineItem, 'getParentProduct') && is_callable([$lineItem, 'getParentProduct'])) {
            /** @var Product $parentProduct */
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
     * @param array $variantFields
     * @param array $customFields
     * @return array
     */
    private function getVariantFields(Product $product, array $variantFields, array $customFields)
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
