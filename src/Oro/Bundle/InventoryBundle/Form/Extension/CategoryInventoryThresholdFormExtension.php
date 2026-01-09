<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Extension\AbstractFallbackCategoryTypeExtension;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This extension adds 'inventoryThreshold' field to category form
 */
class CategoryInventoryThresholdFormExtension extends AbstractFallbackCategoryTypeExtension
{
    #[\Override]
    public function getFallbackProperties()
    {
        return [
            'inventoryThreshold'
        ];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'inventoryThreshold',
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.inventory_threshold.label',
                'required' => true,
            ]
        );
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            [$this, 'onPreSubmitData']
        );
    }

    public function onPreSubmitData(FormEvent $event)
    {
        $data = $event->getData();
        if (isset($data['inventoryThreshold']['useFallback']) && $data['inventoryThreshold']['useFallback'] == '1') {
            $data['inventoryThreshold']['scalarValue'] = 0;
        }
        $event->setData($data);
    }
}
