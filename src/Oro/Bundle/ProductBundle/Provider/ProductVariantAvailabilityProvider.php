<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class ProductVariantAvailabilityProvider
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EnumValueProvider */
    private $enumValueProvider;

    /** @var CustomFieldProvider */
    private $customFieldProvider;

    /** @var PropertyAccessor */
    private $propertyAccessor;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var array */
    private $customFieldsByEntity = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param EnumValueProvider $enumValueProvider
     * @param CustomFieldProvider $customFieldProvider
     * @param PropertyAccessor $propertyAccessor
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EnumValueProvider $enumValueProvider,
        CustomFieldProvider $customFieldProvider,
        PropertyAccessor $propertyAccessor,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->enumValueProvider = $enumValueProvider;
        $this->customFieldProvider = $customFieldProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->eventDispatcher = $eventDispatcher;
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

        $variants = [];
        switch ($type) {
            case 'enum':
                $enumCode = ExtendHelper::generateEnumCode(Product::class, $variantFieldName);
                $variants = $this->enumValueProvider->getEnumChoicesByCode($enumCode);
                break;

            case 'boolean':
                // TODO: Is it possible to have this choice variants in one place?
                $variants = [0 => 'No', 1 => 'Yes'];
                break;

            case null:
                throw new \InvalidArgumentException(
                    sprintf('Custom field "%s" not found', $variantFieldName)
                );
        }

        return $variants;
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

        switch ($fieldType) {
            case 'enum':
                $result = $this->doctrineHelper->getSingleEntityIdentifier($variantValue);
                break;

            case 'boolean':
                $result = (bool) $variantValue;
                break;
            default:
                throw new \InvalidArgumentException(
                    sprintf(
                        'Can not get value of "%s" field from product (ID: %d)',
                        $variantField,
                        $product->getId()
                    )
                );
        }

        return $result;
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
