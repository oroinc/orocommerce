<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductPrimaryUnitPrecisionType extends AbstractType
{
    const NAME = 'oro_product_primary_unit_precision';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'precision',
                IntegerType::class,
                [
                    'type' => 'number',
                    'required' => false,
                    'attr' => [
                        'data-precision' => 0
                    ]
                ]
            )
            ->add(
                'conversionRate',
                HiddenType::class,
                [
                    'data' => 1
                ]
            )
            ->add(
                'sell',
                HiddenType::class,
                [
                    'data' => true
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $unitPrecision = $event->getData();
            $form = $event->getForm();

            if ($unitPrecision instanceof ProductUnitPrecision && $unitPrecision->getUnit()) {
                $form->add(
                    'unit',
                    ProductUnitSelectType::class,
                    [
                        'attr' => ['class' => 'unit'],
                        'product' => $unitPrecision ? $unitPrecision->getProduct() : null
                    ]
                );
            } else {
                $form->add('unit', ProductUnitSelectType::class, ['compact' => $options['compact']]);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'compact' => false
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
