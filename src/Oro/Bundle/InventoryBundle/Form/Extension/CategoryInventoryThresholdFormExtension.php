<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

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

    public function onPreSubmitData(FormEvent $event)
    {
        $data = $event->getData();
        if (isset($data['inventoryThreshold']['useFallback']) && $data['inventoryThreshold']['useFallback'] == '1') {
            $data['inventoryThreshold']['scalarValue'] = 0;
        }
        $event->setData($data);
    }
}
