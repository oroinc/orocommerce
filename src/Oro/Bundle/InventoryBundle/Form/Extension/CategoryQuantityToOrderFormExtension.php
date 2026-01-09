<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Extension\AbstractFallbackCategoryTypeExtension;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\InventoryBundle\Model\Inventory;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Extends category forms with minimum and maximum quantity to order fields.
 */
class CategoryQuantityToOrderFormExtension extends AbstractFallbackCategoryTypeExtension
{
    #[\Override]
    public function getFallbackProperties()
    {
        return [
            Inventory::FIELD_MINIMUM_QUANTITY_TO_ORDER,
            Inventory::FIELD_MAXIMUM_QUANTITY_TO_ORDER
        ];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add(
            Inventory::FIELD_MINIMUM_QUANTITY_TO_ORDER,
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.fields.category.minimum_quantity_to_order.label',
                'required' => false,
            ]
        );

        $builder->add(
            Inventory::FIELD_MAXIMUM_QUANTITY_TO_ORDER,
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.fields.category.maximum_quantity_to_order.label',
                'required' => false,
            ]
        );
    }
}
