<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;

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
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param CustomFieldProvider $customFieldProvider
     * @param ProductVariantAvailabilityProvider $productVariantAvailabilityProvider
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        CustomFieldProvider $customFieldProvider,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        PropertyAccessor $propertyAccessor
    ) {
        $this->customFieldProvider = $customFieldProvider;
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->propertyAccessor = $propertyAccessor;
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
        foreach ($variantFields as $key => $value) {
            $fieldValue = $this->propertyAccessor->getValue($product, $value);
            if ($fieldValue === null) {
                continue;
            }
            $fields[$value] = $this->prepareFieldByType(
                $customFields[$value]['type'],
                $fieldValue,
                $customFields[$value]['label']
            );
        }

        return $fields;
    }

    /**
     * @param $type
     * @param $fieldValue
     * @param $label
     * @return string
     */
    private function prepareFieldByType($type, $fieldValue, $label)
    {
        switch ($type) {
            case 'enum':
                return [
                    'value' => $fieldValue->getId(),
                    'label' => $label,
                    'type' => 'enum'
                ];
            case 'boolean':
                return [
                    'value' => $fieldValue,
                    'label' => $label,
                    'type' => 'boolean'
                ];
            default:
                throw new \LogicException(
                    sprintf(
                        'Incorrect type. Expected "%s", but "%s" given',
                        implode('" or "', ['boolean', 'enum']),
                        $type
                    )
                );
        }
    }
}
