<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Layout data provider for configurable products.
 */
class ConfigurableProductProvider
{
    /** @var CustomFieldProvider */
    protected $customFieldProvider;

    /** @var ProductVariantAvailabilityProvider */
    protected $productVariantAvailabilityProvider;

    /** @var ProductVariantFieldValueHandlerRegistry */
    protected $fieldValueHandlerRegistry;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var array */
    private $customFields = [];

    /** @var array */
    private $translatedCustomFields = [];

    /** @var TranslatorInterface|null */
    private $translator;

    public function __construct(
        CustomFieldProvider $customFieldProvider,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        PropertyAccessor $propertyAccessor,
        ProductVariantFieldValueHandlerRegistry $fieldValueHandlerRegistry,
        TranslatorInterface $translator
    ) {
        $this->customFieldProvider = $customFieldProvider;
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->fieldValueHandlerRegistry = $fieldValueHandlerRegistry;
        $this->translator = $translator;
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
            // Faster than array_replace(...$var) approximately by 8%.
            $variantFieldNames += $this->getVariantFieldsValuesForLineItem($value, false);
        }

        return $variantFieldNames;
    }

    /**
     * @param ProductHolderInterface|mixed $lineItem
     *
     * @return Product|null
     */
    private function getParentProductFromLineItem($lineItem): ?Product
    {
        $parentProduct = null;
        if (is_callable([$lineItem, 'getParentProduct'])) {
            /** @var Product $parentProduct */
            $parentProduct = $lineItem->getParentProduct();
        }

        return $parentProduct;
    }

    private function getProductCustomFields(bool $translateLabels): array
    {
        if (!$this->customFields) {
            $this->customFields = $this->customFieldProvider->getEntityCustomFields(Product::class);
        }

        if ($translateLabels) {
            if (!$this->translatedCustomFields) {
                foreach ($this->customFields as $k => $customField) {
                    $customField['label'] = $this->translator->trans($customField['label']);

                    $this->translatedCustomFields[$k] = $customField;
                }
            }

            return $this->translatedCustomFields;
        }

        return $this->customFields;
    }

    /**
     * @param ProductHolderInterface|mixed $lineItem
     * @param bool $translateLabels
     *
     * @return array
     */
    public function getVariantFieldsValuesForLineItem($lineItem, bool $translateLabels): array
    {
        $variantFieldNames = [];
        $parentProduct = $this->getParentProductFromLineItem($lineItem);
        if ($parentProduct) {
            $simpleProduct = $lineItem->getProduct();
            $variantFieldNames[$simpleProduct->getId()] = $this->getVariantFields(
                $simpleProduct,
                $parentProduct->getVariantFields(),
                $this->getProductCustomFields($translateLabels)
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
