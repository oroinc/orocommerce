<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Extension\AbstractFallbackCategoryTypeExtension;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Symfony\Component\Form\FormBuilderInterface;

class CategoryHighlightLowInventoryFormExtension extends AbstractFallbackCategoryTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFallbackProperties()
    {
        return [
            LowInventoryProvider::HIGHLIGHT_LOW_INVENTORY_OPTION
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
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
