<?php

namespace Oro\Bundle\ProductBundle\VirtualFields;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Extends product with virtual fields
 */
class VirtualFieldsProductDecorator
{
    private const PRODUCT_ID_LABEL = 'product_id';
    private const RELATED_ID_LABEL = 'related_id';

    private VirtualFieldsSelectQueryConverter $converter;
    private ManagerRegistry $doctrine;
    private array $products;
    private Product $product;
    private FieldHelper $fieldHelper;
    private static array $values = [];
    private static ?PropertyAccessorInterface $propertyAccessor = null;
    private CacheInterface $cacheProvider;
    private ConfigProvider $attributeProvider;

    public function __construct(
        VirtualFieldsSelectQueryConverter $converter,
        ManagerRegistry $doctrine,
        FieldHelper $fieldHelper,
        CacheInterface $cacheProvider,
        ConfigProvider $attributeProvider,
        array $products,
        Product $product
    ) {
        $this->doctrine = $doctrine;
        $this->converter = $converter;
        $this->fieldHelper = $fieldHelper;
        $this->cacheProvider = $cacheProvider;
        $this->attributeProvider = $attributeProvider;
        $this->products = $products;
        $this->product = $product;
    }

    public function __get($name) : mixed
    {
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey(
            sprintf('%s_%s', $this->product->getId(), $name)
        );
        return $this->cacheProvider->get($cacheKey, function () use ($name) {
            if ($this->getPropertyAccessor()->isReadable($this->product, $name)) {
                return $this->getReadablePropertyValue($name);
            } else {
                $field = $this->getRelationField($name);
                if (!$field) {
                    throw new \InvalidArgumentException(
                        sprintf('Relation "%s" doesn\'t exists for Product entity', $name)
                    );
                }
                return $this->getVirtualFieldValueForAllProducts($field)[$this->product->getId()];
            }
        });
    }

    protected function getReadablePropertyValue(string $name)
    {
        $propertyValue = $this->getPropertyAccessor()->getValue($this->product, $name);

        if ($propertyValue) {
            return $propertyValue;
        }

        $field = $this->getRelationField($name);

        /**
         * If its dynamic attribute and its value is empty
         * for expression language proper work we need to return attribute stub
         */
        if ($field && $this->fieldHelper->isSingleDynamicAttribute($field)) {
            // AbstractEnumValue array equivalent
            $propertyValue = [
                'id'       => null,
                'name'     => null,
                'priority' => 0,
                'default'  => false,
            ];
        }

        return $propertyValue;
    }

    /**
     * @param string $name
     * @return array|null
     */
    protected function getRelationField($name)
    {
        $fields = $this->fieldHelper->getEntityFields(
            Product::class,
            EntityFieldProvider::OPTION_WITH_RELATIONS | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS
        );
        foreach ($fields as $field) {
            $originalField = $this->getOriginalFieldName($field['name']);
            if ($originalField && $originalField === $name) {
                return $field;
            }

            if ($field['name'] === $name) {
                return $field;
            }
        }
        return null;
    }

    /**
     * @param array $field
     * @return array
     */
    protected function getVirtualFieldValueForAllProducts(array $field)
    {
        if (array_key_exists($field['name'], static::$values)) {
            return static::$values[$field['name']];
        }

        $relatedEntityIdsByProduct = $this->getRelatedEntityIdsByProduct($field);

        $relatedEntities = $this->getRelatedEntities(
            $field['related_entity_name'],
            array_merge(...array_values($relatedEntityIdsByProduct))
        );

        if ($this->fieldHelper->isSingleRelation($field)) {
            static::$values[$field['name']] = $this->getSingleRelationEntity(
                $relatedEntityIdsByProduct,
                $relatedEntities
            );
        } else {
            static::$values[$field['name']] = $this->getMultipleRelationEntities(
                $relatedEntityIdsByProduct,
                $relatedEntities
            );
        }
        return static::$values[$field['name']];
    }

    /**
     * Fetch related entity identifiers for all products and return they by product ids
     * [product_id => [related_entity_identifier_id_1, related_entity_identifier_id_2...]]
     *
     * @param array $field
     * @return array
     */
    protected function getRelatedEntityIdsByProduct(array $field)
    {
        $queryDesigner = new QueryDesigner(
            Product::class,
            QueryDefinitionUtil::encodeDefinition([
                'columns' => [
                    [
                        'name' => 'id',
                        'label' => static::PRODUCT_ID_LABEL
                    ],
                    [
                        'name' => sprintf(
                            '%s+%s::%s',
                            $field['name'],
                            $field['related_entity_name'],
                            $this->getEntityIdentifier($field['related_entity_name'])
                        ),
                        'table_identifier' => sprintf(
                            '%s::%s',
                            Product::class,
                            $field['name']
                        ),
                        'label' => static::RELATED_ID_LABEL
                    ]
                ]
            ])
        );

        $qb = $this->converter->convert($queryDesigner);
        $rootAliases = $qb->getRootAliases();
        $rootAlias = reset($rootAliases);
        $qb
            ->andWhere(sprintf('%s in (:products)', $rootAlias))
            ->setParameter('products', $this->products);

        return array_reduce($qb->getQuery()->getResult(), function ($result, $data) {
            if (!array_key_exists($data[static::PRODUCT_ID_LABEL], $result)) {
                $result[$data[static::PRODUCT_ID_LABEL]] = [];
            }
            if ($data[static::RELATED_ID_LABEL]) {
                $result[$data[static::PRODUCT_ID_LABEL]][] = $data[static::RELATED_ID_LABEL];
            }
            return $result;
        }, []);
    }

    /**
     * @param string $className
     * @param array $ids
     * @return array
     */
    protected function getRelatedEntities($className, array $ids)
    {
        $relatedEntityIdentifier = $this->getEntityIdentifier($className);
        $relatedEntities = $this->doctrine->getManagerForClass($className)->getRepository($className)
            ->findBy([$relatedEntityIdentifier => $ids]);

        return array_reduce(
            $relatedEntities,
            function ($result, $relatedEntity) use ($relatedEntityIdentifier) {
                $id = $this->getPropertyAccessor()->getValue($relatedEntity, $relatedEntityIdentifier);
                $result[$id] = $relatedEntity;
                return $result;
            },
            []
        );
    }

    /**
     * @param array $relatedEntityIdsByProduct
     * @param array $relatedEntities
     * @return array
     */
    protected function getMultipleRelationEntities(array $relatedEntityIdsByProduct, array $relatedEntities)
    {
        return array_reduce(
            array_keys($relatedEntityIdsByProduct),
            function ($result, $productId) use ($relatedEntityIdsByProduct, $relatedEntities) {
                $result[$productId] = array_map(function ($id) use ($relatedEntities) {
                    return $relatedEntities[$id];
                }, $relatedEntityIdsByProduct[$productId]);
                return $result;
            },
            []
        );
    }

    /**
     * @param array $relatedEntityIdsByProduct
     * @param array $relatedEntities
     * @return array
     */
    protected function getSingleRelationEntity(array $relatedEntityIdsByProduct, array $relatedEntities)
    {
        return array_reduce(
            array_keys($relatedEntityIdsByProduct),
            function ($result, $productId) use ($relatedEntityIdsByProduct, $relatedEntities) {
                $relatedIds = $relatedEntityIdsByProduct[$productId];
                $result[$productId] = $relatedEntities[reset($relatedIds)];
                return $result;
            },
            []
        );
    }

    /**
     * @param string $className
     * @return string
     */
    protected function getEntityIdentifier($className)
    {
        $metadata = $this->doctrine->getManagerForClass($className)->getClassMetadata($className);
        $identifier = $metadata->getIdentifier();
        return reset($identifier);
    }

    /**
     * @return PropertyAccessorInterface
     */
    protected function getPropertyAccessor()
    {
        if (!static::$propertyAccessor) {
            static::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return static::$propertyAccessor;
    }

    protected function getOriginalFieldName(string $fieldName): ?string
    {
        if ($this->attributeProvider->hasConfig(Product::class, $fieldName)) {
            return $this->attributeProvider->getConfig(Product::class, $fieldName)->get('field_name');
        }

        return null;
    }
}
