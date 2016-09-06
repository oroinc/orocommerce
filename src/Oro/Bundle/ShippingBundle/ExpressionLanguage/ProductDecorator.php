<?php

namespace Oro\Bundle\ShippingBundle\ExpressionLanguage;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\QueryDesigner\Converter;
use Oro\Bundle\ShippingBundle\QueryDesigner\QueryDesigner;
use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ProductDecorator
{
    const PRODUCT_ID_LABEL = 'product_id';
    const RELATED_ID_LABEL = 'related_id';

    /**
     * @var EntityFieldProvider
     */
    protected $provider;

    /**
     * @var Converter
     */
    protected $converter;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var Collection
     */
    protected $lineItems;

    /**
     * @var Collection
     */
    protected $product;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var PropertyAccessor
     */
    protected static $propertyAccessor;

    /**
     * @param EntityFieldProvider $provider
     * @param Converter $converter
     * @param ManagerRegistry $doctrine
     * @param Collection $lineItems
     * @param Product $product
     */
    public function __construct(
        EntityFieldProvider $provider,
        Converter $converter,
        ManagerRegistry $doctrine,
        Collection $lineItems,
        Product $product
    ) {
        $this->provider = $provider;
        $this->doctrine = $doctrine;
        $this->converter = $converter;
        $this->lineItems = $lineItems;
        $this->product = $product;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        if ($this->getPropertyAccessor()->isReadable($this->product, $name)) {
            return $this->getPropertyAccessor()->getValue($this->product, $name);
        }
        $field = $this->getField($name);
        if (!$field) {
            throw new \Exception('Field doesn\'t exists: '.$name);
        }
        return $this->getVirtualFieldValue($field);
    }

    /**
     * @param string $name
     * @return array|null
     */
    protected function getField($name)
    {
        $fields = $this->provider->getFields(Product::class, true, true, true);
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
    protected function getVirtualFieldValue(array $field)
    {
        $queryDesigner = new QueryDesigner();
        $queryDesigner->setEntity(Product::class);
        $queryDesigner->setDefinition(json_encode([
            'columns' => [
                [
                    'name' => 'id',
                    'label' => static::PRODUCT_ID_LABEL,
                ],
                [
                    'name' => sprintf(
                        '%s+%s::%s',
                        $field['name'],
                        $field['related_entity_name'],
                        //TODO: get identifier field
                        'id'
                    ),
                    'table_identifier' => sprintf(
                        '%s::%s',
                        Product::class,
                        $field['name']
                    ),
                    'label' => static::RELATED_ID_LABEL,
                ]
            ]
        ]));

        $qb = $this->converter->convert($queryDesigner);
        $result = $qb->getQuery()->getResult();

        $ids = array_column(array_filter($result, function ($data) {
            return $data[static::PRODUCT_ID_LABEL] === $this->product->getId();
        }), static::RELATED_ID_LABEL);

        //TODO: add checking for relation type
        //if ref-one - use findOneBy

        return $this->doctrine->getManagerForClass($field['related_entity_name'])
            ->getRepository($field['related_entity_name'])
            ->findBy(['id' => $ids]);
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
