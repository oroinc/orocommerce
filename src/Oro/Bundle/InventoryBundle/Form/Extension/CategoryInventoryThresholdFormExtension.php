<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;

class CategoryInventoryThresholdFormExtension extends AbstractTypeExtension
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
            'inventoryThreshold',
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.inventory.inventory_threshold.label',
                'required' => true,
                'value_options' => [
                    'constraints' => [
                        new Decimal(),
                        new NotBlank()
                    ]
                ]
            ]
        );
    }
}
