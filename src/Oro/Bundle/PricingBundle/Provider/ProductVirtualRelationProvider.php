<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVirtualRelationProvider implements VirtualRelationProviderInterface
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var array
     */
    protected $virtualFields = [];

    /**
     * @var array
     */
    protected $productAttributes = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isVirtualRelation($className, $fieldName)
    {
        return ($className == Product::class) && $this->isProductAttributeField($fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelationQuery($className, $fieldName)
    {
        $relations = $this->getVirtualRelations($className);

        if (array_key_exists($fieldName, $relations)) {
            return $relations[$fieldName]['query'];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        $relations = [];

        if ($className == Product::class) {
            $productAttributeFieldNames = $this->getProductAttributes();
            foreach ($productAttributeFieldNames as $attribute) {
                $relations[$attribute['fieldName']] = $this->getRelationDefinition($attribute);
            }
        }

        return $relations;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null)
    {
        return $fieldName . 'Price';
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    protected function isProductAttributeField($fieldName)
    {
        $priceAttributeFieldNames = array_map(
            function ($item) {
                return $item['fieldName'];
            },
            $productAttributeFieldNames = $this->getProductAttributes()
        );
        return in_array($fieldName, $priceAttributeFieldNames, true);
    }

    /**
     * @return array
     */
    protected function getProductAttributes()
    {
        if (!$this->productAttributes) {
            /** @var PriceAttributePriceListRepository $repository */
            $repository = $this->doctrineHelper->getEntityRepository(PriceAttributePriceList::class);

            $this->productAttributes = $repository->getFieldNames();
        }

        return $this->productAttributes;
    }

    /**
     * @param array $attribute
     * @return array
     */
    protected function getRelationDefinition(array $attribute)
    {
        $priceAlias = $attribute['fieldName'] . 'Price';

        return [
            'label' => $attribute['name'],
            'relation_type' => 'manyToOne',
            'related_entity_name' => PriceAttributeProductPrice::class,
            'target_join_alias' => $priceAlias,
            'query' => [
                'join' => [
                    'left' => [
                        [
                            'join' => PriceAttributeProductPrice::class,
                            'alias' => $priceAlias,
                            'conditionType' => Join::WITH,
                            'condition' => sprintf(
                                '(%s.product = entity and %s.priceList = %d)',
                                $priceAlias,
                                $priceAlias,
                                $attribute['id']
                            )
                        ]
                    ],
                ],
            ],
        ];
    }
}
