<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Get product variants by configurable products.
 * Get products fields availability for given configurable products.
 */
class ProductVariantAvailabilityProvider
{
    private ManagerRegistry $doctrine;
    private CustomFieldProvider $customFieldProvider;
    private PropertyAccessorInterface $propertyAccessor;
    private EventDispatcherInterface $eventDispatcher;
    private ProductVariantFieldValueHandlerRegistry $fieldValueHandlerRegistry;

    /** @var array [entity class => [field name => field data (array), ...], ...] */
    private array $customFieldsByEntity = [];
    /** @var array [cache key => data, ...] */
    private array $productsByVariantFieldsCache = [];

    public function __construct(
        ManagerRegistry $doctrine,
        CustomFieldProvider $customFieldProvider,
        PropertyAccessorInterface $propertyAccessor,
        EventDispatcherInterface $eventDispatcher,
        ProductVariantFieldValueHandlerRegistry $fieldValueHandlerRegistry
    ) {
        $this->doctrine = $doctrine;
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
                if ($variantFieldValue === null) {
                    continue;
                }
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

        return \array_key_exists($fieldName, $customFields) ? $customFields[$fieldName]['type'] : null;
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
        $cacheKey = $this->getProductsByVariantsCacheKey($configurableProduct->getId(), $variantParameters);
        if (!isset($this->productsByVariantFieldsCache[$cacheKey])) {
            $result = $this->getSimpleProductsByVariantFieldsQB($configurableProduct, $variantParameters)
                ->getQuery()
                ->getResult();
            $this->productsByVariantFieldsCache[$cacheKey] = $result;
        }

        return $this->productsByVariantFieldsCache[$cacheKey];
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
     * @return bool
     */
    public function hasSimpleProductsByVariantFields(Product $configurableProduct, array $variantParameters = [])
    {
        $count = (int)$this->getSimpleProductsByVariantFieldsQB($configurableProduct, $variantParameters)
            ->resetDQLPart('select')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
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
     * @param int[] $configurableProductIds
     *
     * @return array [configurable product id => [simple product id, ...], ...]
     */
    public function getSimpleProductIdsByVariantFieldsGroupedByConfigurable(array $configurableProductIds): array
    {
        if (!$configurableProductIds) {
            throw new \InvalidArgumentException('The list of configurable product IDs must not be empty.');
        }

        $qb = $this->getProductRepository()
            ->getSimpleProductIdsByParentProductsQueryBuilder($configurableProductIds)
            ->select('p.id AS productId, IDENTITY(l.parentProduct) AS parentProductId');
        $rows = $this->modifyRestrictProductVariantQueryBuilder($qb)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['parentProductId']][] = (int)$row['productId'];
        }

        return $result;
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
     * @param string  $variantField
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function getVariantFieldScalarValue(Product $product, $variantField)
    {
        $handler = $this->fieldValueHandlerRegistry->getVariantFieldValueHandler(
            $this->getCustomFieldType($variantField)
        );

        return $handler->getScalarValue($this->propertyAccessor->getValue($product, $variantField));
    }

    /**
     * Returns a list of simple products grouped by configurable product IDs for given configurable products.
     *
     * @param int[] $configurableProductIds
     *
     * @return array [configurable product id => [simple product id, ...], ...]
     */
    public function getSimpleProductIdsGroupedByConfigurable(array $configurableProductIds): array
    {
        if (!$configurableProductIds) {
            throw new \InvalidArgumentException('The list of configurable product IDs must not be empty.');
        }

        $simpleProductIds = $this->getSimpleProductIdsByConfigurable($configurableProductIds);
        if (!$simpleProductIds) {
            return [];
        }

        $mapping = $this->getProductRepository()->getVariantsMapping($configurableProductIds);
        $simpleByConfigurable = [];
        foreach ($simpleProductIds as $simpleProductId) {
            if (empty($mapping[$simpleProductId])) {
                continue;
            }
            foreach ($mapping[$simpleProductId] as $parentId) {
                $simpleByConfigurable[$parentId][] = $simpleProductId;
            }
        }

        return $simpleByConfigurable;
    }

    /**
     * Return array of simple products by given configurable products.
     *
     * @param int[] $configurableProductIds
     *
     * @return int[]
     */
    public function getSimpleProductIdsByConfigurable(array $configurableProductIds): array
    {
        $qb = $this->getProductRepository()->getSimpleProductIdsByParentProductsQueryBuilder($configurableProductIds);
        $rows = $this->modifyRestrictProductVariantQueryBuilder($qb)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }

    private function getProductsByVariantsCacheKey(int $configurableProductId, array $variantParameters = []): string
    {
        return md5($configurableProductId . json_encode($variantParameters, JSON_THROW_ON_ERROR));
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getSimpleProductsByVariantFieldsQB(Product $configurableProduct, array $variantParameters = [])
    {
        $this->ensureProductTypeIsConfigurable($configurableProduct);

        $qb = $this->getProductRepository()->getSimpleProductsByVariantFieldsQueryBuilder(
            $configurableProduct,
            $variantParameters
        );

        return $this->modifyRestrictProductVariantQueryBuilder($qb);
    }

    /**
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
        if (!\array_key_exists($entityName, $this->customFieldsByEntity)) {
            $this->customFieldsByEntity[$entityName] = $this->customFieldProvider->getEntityCustomFields($entityName);
        }

        return $this->customFieldsByEntity[$entityName];
    }

    private function modifyRestrictProductVariantQueryBuilder(QueryBuilder $qb): QueryBuilder
    {
        $event = new RestrictProductVariantEvent($qb);
        $this->eventDispatcher->dispatch($event, RestrictProductVariantEvent::NAME);

        return $event->getQueryBuilder();
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->doctrine->getRepository(Product::class);
    }
}
