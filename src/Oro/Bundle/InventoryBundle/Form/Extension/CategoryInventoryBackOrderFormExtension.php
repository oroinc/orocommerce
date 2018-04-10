<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Extension\AbstractFallbackCategoryTypeExtension;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Symfony\Component\Form\FormBuilderInterface;

class CategoryInventoryBackOrderFormExtension extends AbstractFallbackCategoryTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFallbackProperties()
    {
        return [
            'backOrder'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'backOrder',
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.backorders.label',
            ]
        );
    }
}
