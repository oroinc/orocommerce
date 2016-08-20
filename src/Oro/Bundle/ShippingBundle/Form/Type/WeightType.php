<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ShippingBundle\Form\DataTransformer\WeightTransformer;

class WeightType extends AbstractType
{
    const NAME = 'orob2b_shipping_weight';

    /** @var string */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'value',
                NumberType::class,
                [
                    'attr' => ['class' => 'value'],
                    'required' => false,
                ]
            )
            ->add(
                'unit',
                WeightUnitSelectType::NAME,
                [
                    'placeholder' => 'oro.shipping.form.placeholder.weight_unit.label',
                    'required' => false,
                ]
            );

        $builder->addViewTransformer(new WeightTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
