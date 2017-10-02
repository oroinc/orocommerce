<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;

class CategoryLowInventoryThresholdFormExtension extends AbstractTypeExtension
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
            'lowInventoryThreshold',
            EntityFieldFallbackValueType::NAME,
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

        if (isset($data['lowInventoryThreshold']['useFallback'])
            && $data['lowInventoryThreshold']['useFallback'] == '1'
        ) {
            $data['lowInventoryThreshold']['scalarValue'] = 0;
        }

        $event->setData($data);
    }
}
