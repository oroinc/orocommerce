<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\VirtualRelationProviderInterface;

use OroB2B\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductVirtualRelationProvider implements VirtualRelationProviderInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var array */
    protected $virtualFields = [];

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

        return isset($relations[$fieldName])
            ? $relations[$fieldName]['query']
            : [];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getVirtualRelations($className)
    {
        $relations = [];

        if ($className == Product::class) {
            $productAttributeFieldNames = $this->getProductAttributesFieldNames();
            foreach ($productAttributeFieldNames as $label => $fieldName) {
                $relations[$fieldName] = $this->getRelationDefinition($className, $label, $fieldName);
            }
        }

        return $relations;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetJoinAlias($className, $fieldName, $selectFieldName = null)
    {
        return $fieldName.'Price';
    }

    /**
     * @param string $fieldName
     * @return bool
     */
    protected function isProductAttributeField($fieldName)
    {
        return in_array($fieldName, $this->getProductAttributesFieldNames(), true);
    }

    /**
     * @return array
     */
    protected function getProductAttributesFieldNames()
    {
        /** @var PriceAttributePriceListRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(PriceAttributePriceList::class);
        return $repository->getFieldNames();
    }

    /**
     * @param string $className
     * @param string $label
     * @param string $fieldName
     * @return array
     */
    protected function getRelationDefinition($className, $label, $fieldName)
    {
        $priceAlias = $fieldName.'Price';
        $priceAttributeAlias = $fieldName.'PriceAttribute';

        return [
            'label' => $label,
            'relation_type' => 'OneToMany',
            'related_entity_name' => PriceAttributeProductPrice::class,
            'target_join_alias' => $priceAlias,
            'query' => [
                'join' => [
                    'left' => [
                        [
                            'join' => PriceAttributeProductPrice::class,
                            'alias' => $priceAlias,
                            'conditionType' => Join::WITH,
                            'condition' => '('. $priceAlias .'.product = entity)'
                        ],
                        [
                            'join' => PriceAttributePriceList::class,
                            'alias' => $priceAttributeAlias,
                            'conditionType' => Join::WITH,
                            'condition' => '('. $priceAlias .'.priceList = '. $priceAttributeAlias .')'
                        ]
                    ]
                ]
            ]
        ];
    }
}
