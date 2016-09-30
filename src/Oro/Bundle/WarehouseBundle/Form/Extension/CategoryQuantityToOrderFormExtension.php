<?php

namespace Oro\Bundle\WarehouseBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Integer;
use Oro\Bundle\WarehouseBundle\Migrations\Schema\v1_2\AddQuantityToOrderFields;

class CategoryQuantityToOrderFormExtension extends AbstractTypeExtension
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
            AddQuantityToOrderFields::FIELD_MINIMUM_QUANTITY_TO_ORDER,
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.warehouse.fields.category.minimum_quantity_to_order.label',
                'value_options' => [
                    'constraints' => new Integer(),
                ],
            ]
        )->add(
            AddQuantityToOrderFields::FIELD_MAXIMUM_QUANTITY_TO_ORDER,
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.warehouse.fields.category.maximum_quantity_to_order.label',
                'value_options' => [
                    'constraints' => new Integer(),
                ],
            ]
        );
    }
}
