<?php

namespace Oro\Bundle\ProductBundle\VirtualFields;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter;
use Oro\Bundle\QueryDesignerBundle\Model\QueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Extends product with virtual fields
 */
class VirtualFieldsProductDecorator
{
    const PRODUCT_ID_LABEL = 'product_id';
    const RELATED_ID_LABEL = 'related_id';

    /**
     * @var VirtualFieldsSelectQueryConverter
     */
    protected $converter;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var Collection|Product[]
     */
    protected $products;

    /**
     * @var Collection
     */
    protected $product;

    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var array
     */
    protected static $values = [];

    /**
     * @var PropertyAccessor
     */
    protected static $propertyAccessor;

    /**
     * @var CacheProvider
     */
    private $cacheProvider;

    public function __construct(
        VirtualFieldsSelectQueryConverter $converter,
        ManagerRegistry $doctrine,
        FieldHelper $fieldHelper,
        CacheProvider $cacheProvider,
        array $products,
        Product $product
    ) {
        $this->doctrine = $doctrine;
        $this->converter = $converter;
        $this->fieldHelper = $fieldHelper;
        $this->cacheProvider = $cacheProvider;
        $this->products = $products;
        $this->product = $product;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        $cacheKey = sprintf('%s_%s', $this->product->getId(), $name);

        //Check contains before fetch considering bool value can be returned
        if (!$this->cacheProvider->contains($cacheKey)) {
            if ($this->getPropertyAccessor()->isReadable($this->product, $name)) {
                $propertyValue = $this->getPropertyAccessor()->getValue($this->product, $name);
            } else {
                $field = $this->getRelationField($name);
                if (!$field) {
                    throw new \InvalidArgumentException(
                        sprintf('Relation "%s" doesn\'t exists for Product entity', $name)
                    );
                }

                $propertyValue = $this->getVirtualFieldValueForAllProducts($field)[$this->product->getId()];
            }
            $this->cacheProvider->save($cacheKey, $propertyValue);
        } else {
            $propertyValue = $this->cacheProvider->fetch($cacheKey);
        }

        return $propertyValue;
    }

    /**
     * @param string $name
     * @return array|null
     */
    protected function getRelationField($name)
    {
        $fields = $this->fieldHelper->getFields(Product::class, true, true, false, false, true, false);
        foreach ($fields as $field) {
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
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!static::$propertyAccessor) {
            static::$propertyAccessor = new PropertyAccessor();
        }

        return static::$propertyAccessor;
    }
}
