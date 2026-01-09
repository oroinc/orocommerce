<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Extension\AbstractFallbackCategoryTypeExtension;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Extends the category form with the highlight low inventory field.
 */
class CategoryHighlightLowInventoryFormExtension extends AbstractFallbackCategoryTypeExtension
{
    #[\Override]
    public function getFallbackProperties()
    {
        return [
            LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION
        ];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add(
            LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION,
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.highlight_low_inventory.label',
            ]
        );
    }
}
