<?php

namespace Oro\Bundle\ProductBundle\DataGrid\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;

/**
 * Adds configuration of the attribute family field.
 */
class AttributeFamilyColumnOnProductsListener
{
    private EntityNameResolver $entityNameResolver;
    private ManagerRegistry $doctrine;

    public function __construct(EntityNameResolver $entityNameResolver, ManagerRegistry $doctrine)
    {
        $this->entityNameResolver = $entityNameResolver;
        $this->doctrine = $doctrine;
    }

    public function onBuildBefore(BuildBefore $event)
    {
        $config   = $event->getConfig();

        $dql = $this->entityNameResolver->getNameDQL(
            AttributeFamily::class,
            'af',
            EntityNameProviderInterface::FULL
        );

        $config->addColumn(
            'attributeFamily',
            ['label' => 'oro.product.attribute_family.label'],
            $dql . ' AS attributeFamily',
            ['data_name' => 'product.attributeFamily'],
            [
                'data_name' => 'product.attributeFamily',
                'type' => 'choice-tree',
                'label' => 'oro.product.attribute_family.label',
                'autocomplete_alias' => 'oro_product_families',
                'renderedPropertyName' => 'defaultLabel',
                'className' => AttributeFamily::class
            ]
        );

        $config->offsetSetByPath(
            '[fields_acl][columns][attributeFamily]',
            [
                'data_name' => 'product.attributeFamily',
                'column_name' => 'attribute_family'
            ]
        );

        $query = $config->getOrmQuery();
        $query->addLeftJoin('product.attributeFamily', 'af');
    }
}
