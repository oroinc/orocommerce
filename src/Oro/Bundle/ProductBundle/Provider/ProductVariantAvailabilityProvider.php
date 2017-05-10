<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductVariantAvailabilityProvider
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var CustomFieldProvider */
    private $customFieldProvider;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var array */
    private $customFieldsByEntity = [];

    /** @var ProductVariantFieldValueHandlerRegistry */
    private $fieldValueHandlerRegistry;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param CustomFieldProvider $customFieldProvider
     * @param PropertyAccessor $propertyAccessor
     * @param EventDispatcherInterface $eventDispatcher
     * @param ProductVariantFieldValueHandlerRegistry $fieldValueHandlerRegistry
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        CustomFieldProvider $customFieldProvider,
        PropertyAccessor $propertyAccessor,
        EventDispatcherInterface $eventDispatcher,
        ProductVariantFieldValueHandlerRegistry $fieldValueHandlerRegistry
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->customFieldProvider = $customFieldProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->eventDispatcher = $eventDispatcher;
        $this->fieldValueHandlerRegistry = $fieldValueHandlerRegistry;
    }

    /**
     * Get variant fields availability with condition
     *
     * Example of result:
     *  [
     *     'size' => ['m' => false, 'l' => true],
     *     'color' => ['red' => true],
     *     'slim_fit' => true
     * ]
     *
     * @param Product $configurableProduct
     * @param array $variantParameters Variant field conditions
     * @return array
     */
    public function getVariantFieldsAvailability(Product $configurableProduct, array $variantParameters = [])
    {
        $this->ensureProductTypeIsConfigurable($configurableProduct);

        $availableSimpleProducts = $this->getSimpleProductsByVariantFields(
            $configurableProduct,
            $variantParameters
        );

        $variantsAvailability = [];
        foreach ($configurableProduct->getVariantFields() as $variantField) {
            $fieldValues = $this->getVariantFieldValues($variantField);

            // All fields are not available by default
            $variantsAvailability[$variantField] = array_fill_keys(array_keys($fieldValues), false);

            foreach ($availableSimpleProducts as $simpleProduct) {
                $variantFieldValue = $this->getVariantFieldScalarValue($simpleProduct, $variantField);
                $variantsAvailability[$variantField][$variantFieldValue] = true;
            }
        }

        return $variantsAvailability;
    }

    /**
     * Returns all values for specified variant field
     *
     * @param string $variantFieldName
     * @return array
     */
    public function getVariantFieldValues($variantFieldName)
    {
        $type = $this->getCustomFieldType($variantFieldName);
        $handler = $this->fieldValueHandlerRegistry->getVariantFieldValueHandler($type);

        return $handler->getPossibleValues($variantFieldName);
    }

    /**
     * Returns type of custom field
     *
     * @param string $fieldName Custom field name
     * @return string|null Type of custom field, null in case of custom field with specified name doesn't exist
     */
    public function getCustomFieldType($fieldName)
    {
        $customFields = $this->getCustomFieldsByEntity(Product::class);

        return array_key_exists($fieldName, $customFields) ? $customFields[$fieldName]['type'] : null;
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * $variantParameters = [
     *     'size' => 'm',
     *     'color' => 'red',
     *     'slim_fit' => true
     * ]
     * Value is extended field id for select field and true or false for boolean field
     * @return Product[]
     */
    public function getSimpleProductsByVariantFields(Product $configurableProduct, array $variantParameters = [])
    {
        $this->ensureProductTypeIsConfigurable($configurableProduct);

        /** @var ProductRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Product::class);

        $qb = $repository->getSimpleProductsByVariantFieldsQueryBuilder($configurableProduct, $variantParameters);

        $restrictProductVariantEvent = new RestrictProductVariantEvent($qb);
        $this->eventDispatcher->dispatch(RestrictProductVariantEvent::NAME, $restrictProductVariantEvent);

        return $restrictProductVariantEvent
            ->getQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @param bool $throwException
     * @return null|Product
     * @throws \InvalidArgumentException
     */
    public function getSimpleProductByVariantFields(
        Product $configurableProduct,
        array $variantParameters = [],
        $throwException = true
    ) {
        $this->ensureProductTypeIsConfigurable($configurableProduct);
        $simpleProducts = $this->getSimpleProductsByVariantFields($configurableProduct, $variantParameters);

        if ($throwException && count($simpleProducts) !== 1) {
            throw new \InvalidArgumentException('Variant values provided don\'t match exactly one simple product');
        }

        return $simpleProducts ? reset($simpleProducts) : null;
    }

    /**
     * @param Product $configurableProduct
     * @param Product $variantProduct
     * @return array
     */
    public function getVariantFieldsValuesForVariant(Product $configurableProduct, Product $variantProduct)
    {
        $this->ensureProductTypeIsConfigurable($configurableProduct);

        $variantFieldsForVariant = [];
        foreach ($configurableProduct->getVariantFields() as $variantField) {
            $variantFieldsForVariant[$variantField] = $this->getVariantFieldScalarValue($variantProduct, $variantField);
        }

        return $variantFieldsForVariant;
    }

    /**
     * Get value of variant field from product
     *
     * @param Product $product
     * @param string $variantField
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function getVariantFieldScalarValue(Product $product, $variantField)
    {
        $variantValue = $this->propertyAccessor->getValue($product, $variantField);
        $fieldType = $this->getCustomFieldType($variantField);
        $handler = $this->fieldValueHandlerRegistry->getVariantFieldValueHandler($fieldType);

        return $handler->getScalarValue($variantValue);
    }

    /**
     * @param Product $product
     * @throws \InvalidArgumentException
     */
    private function ensureProductTypeIsConfigurable(Product $product)
    {
        if (!$product->isConfigurable()) {
            throw new \InvalidArgumentException(
                sprintf('Product with type "%s" expected, "%s" given', Product::TYPE_CONFIGURABLE, $product->getType())
            );
        }
    }

    /**
     * @param string $entityName
     * @return array
     */
    private function getCustomFieldsByEntity($entityName)
    {
        if (!array_key_exists($entityName, $this->customFieldsByEntity)) {
            $this->customFieldsByEntity[$entityName] = $this->customFieldProvider->getEntityCustomFields($entityName);
        }

        return $this->customFieldsByEntity[$entityName];
    }
}
