<?php

namespace Oro\Bundle\WarehouseBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;

class WarehouseCategoryTypeFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'manageInventory',
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.warehouse.manage_inventory.label',
                'fallback_translation_prefix' => 'oro.warehouse.fallback',
            ]
        );
    }
}
