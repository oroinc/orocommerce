<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Oro\Bundle\CatalogBundle\Form\Extension\AbstractFallbackCategoryTypeExtension;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategoryLowInventoryThresholdFormExtension extends AbstractFallbackCategoryTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFallbackProperties()
    {
        return [
            LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION,
            EntityFieldFallbackValueType::class,
            [
                'label' => 'oro.inventory.low_inventory_threshold.label',
                'required' => true,
                'value_options' => [
                    'constraints' => [
                        new Decimal(),
                        new NotBlank(),
                    ]
                ]
            ]
        );
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            [$this, 'onPreSubmitData']
        );
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSubmitData(FormEvent $event)
    {
        $data = $event->getData();

        if (isset($data[LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION]['useFallback'])
            && $data[LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION]['useFallback'] == '1'
        ) {
            $data[LowInventoryProvider::LOW_INVENTORY_THRESHOLD_OPTION]['scalarValue'] = 0;
        }

        $event->setData($data);
    }
}
