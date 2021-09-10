<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Persistence\Proxy;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Get product variants by configurable products.
 * Get products fields availability for given configurable products.
 */
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

    /** @var AclHelper */
    private $aclHelper;

    /** @var ArrayCache */
    private $productsByVariantFieldsCache;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        CustomFieldProvider $customFieldProvider,
        PropertyAccessor $propertyAccessor,
        EventDispatcherInterface $eventDispatcher,
        ProductVariantFieldValueHandlerRegistry $fieldValueHandlerRegistry,
        AclHelper $aclHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->customFieldProvider = $customFieldProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->eventDispatcher = $eventDispatcher;
        $this->fieldValueHandlerRegistry = $fieldValueHandlerRegistry;
        $this->aclHelper = $aclHelper;

        $this->productsByVariantFieldsCache = new ArrayCache();
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
        $cacheKey = $this->getProductsByVariantsCacheKey($configurableProduct, $variantParameters);
        if (!$this->productsByVariantFieldsCache->contains($cacheKey)) {
            $result = $this->getSimpleProductsByVariantFieldsQB($configurableProduct, $variantParameters)
                ->getQuery()
                ->getResult();
            $this->productsByVariantFieldsCache->save($cacheKey, $result);
        }

        return $this->productsByVariantFieldsCache->fetch($cacheKey);
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
     * Return array of simple products grouped by configurable product ids by given configurable products.
     *
     * @param array|Product[] $products
     * @return array|Product[]
     *
     * [configurableProductId:int => simpleProducts:Product[]]
     */
    public function getSimpleProductsGroupedByConfigurable(array $products): array
    {
        $configurableProducts = $this->filterConfigurableProducts($products);
        if (!$configurableProducts) {
            return [];
        }

        $simpleProducts = $this->getSimpleProductsByConfigurable($configurableProducts);
        if (!$simpleProducts) {
            return [];
        }

        /** @var ProductRepository $productRepository */
        $productRepository = $this->doctrineHelper->getEntityRepository(Product::class);
        $mapping = $productRepository->getVariantsMapping($configurableProducts);
        $simpleByConfigurable = [];
        foreach ($simpleProducts as $simpleProduct) {
            $id = $simpleProduct->getId();
            if (empty($mapping[$id])) {
                continue;
            }
            foreach ($mapping[$id] as $parentId) {
                $simpleByConfigurable[$parentId][] = $simpleProduct;
            }
        }

        return $simpleByConfigurable;
    }

    /**
     * Return array of simple products by given configurable products.
     *
     * @param array $configurableProducts
     * @return array|Product[]
     */
    public function getSimpleProductsByConfigurable(array $configurableProducts): array
    {
        /** @var ProductRepository $productRepository */
        $productRepository = $this->doctrineHelper->getEntityRepository(Product::class);
        $qb = $productRepository->getSimpleProductIdsByParentProductsQueryBuilder($configurableProducts);

        $restrictProductVariantEvent = new RestrictProductVariantEvent($qb);
        $this->eventDispatcher->dispatch($restrictProductVariantEvent, RestrictProductVariantEvent::NAME);

        $em = $this->doctrineHelper->getEntityManager(Product::class);

        return array_map(
            static function ($id) use ($em) {
                return $em->getReference(Product::class, $id);
            },
            array_column($restrictProductVariantEvent->getQueryBuilder()->getQuery()->getArrayResult(), 'id')
        );
    }

    /**
     * @param array|Product[] $products
     * @return array|Product[]
     */
    public function filterConfigurableProducts(array $products): array
    {
        $productsToLoadFromDb = [];
        $configurableProducts = [];
        // To not load proxies in loop collect all uninitialized products to load them all later by 1 query
        foreach ($products as $product) {
            if (!$product instanceof Proxy || ($product instanceof Proxy && $product->__isInitialized())) {
                if ($product->isConfigurable()) {
                    $configurableProducts[] = $product;
                }
            } else {
                $productsToLoadFromDb[] = $product;
            }
        }

        // Load information about configurable products from DB for non-initialized proxies
        if ($productsToLoadFromDb) {
            /** @var ProductRepository $productRepository */
            $productRepository = $this->doctrineHelper->getEntityRepository(Product::class);
            $qb = $productRepository->getConfigurableProductIdsQueryBuilder($productsToLoadFromDb);
            $configurableProductIds = array_column($this->aclHelper->apply($qb)->getArrayResult(), 'id');
            if (!$configurableProductIds) {
                return $configurableProducts;
            }

            $configurableProducts = array_merge(
                $configurableProducts,
                array_filter(
                    $productsToLoadFromDb,
                    static function (Product $product) use ($configurableProductIds) {
                        return \in_array($product->getId(), $configurableProductIds, true);
                    }
                )
            );
        }

        return $configurableProducts;
    }

    private function getProductsByVariantsCacheKey(Product $configurableProduct, array $variantParameters = []): string
    {
        return md5($configurableProduct->getId() . json_encode($variantParameters));
    }

    /**
     * @param Product $configurableProduct
     * @param array $variantParameters
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getSimpleProductsByVariantFieldsQB(Product $configurableProduct, array $variantParameters = [])
    {
        $this->ensureProductTypeIsConfigurable($configurableProduct);

        /** @var ProductRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(Product::class);

        $qb = $repository->getSimpleProductsByVariantFieldsQueryBuilder($configurableProduct, $variantParameters);

        $restrictProductVariantEvent = new RestrictProductVariantEvent($qb);
        $this->eventDispatcher->dispatch($restrictProductVariantEvent, RestrictProductVariantEvent::NAME);

        return $restrictProductVariantEvent->getQueryBuilder();
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
        if (!array_key_exists($entityName, $this->customFieldsByEntity)) {
            $this->customFieldsByEntity[$entityName] = $this->customFieldProvider->getEntityCustomFields($entityName);
        }

        return $this->customFieldsByEntity[$entityName];
    }
}
