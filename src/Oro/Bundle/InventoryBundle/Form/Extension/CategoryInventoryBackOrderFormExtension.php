<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CatalogBundle\Form\Extension\AbstractFallbackCategoryTypeExtension;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;

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
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.inventory.backorders.label',
            ]
        );
    }
}
