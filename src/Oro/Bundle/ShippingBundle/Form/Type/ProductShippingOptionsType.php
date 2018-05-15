<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductShippingOptionsType extends AbstractType
{
    const NAME = 'oro_shipping_product_shipping_options';

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
                'productUnit',
                ProductUnitSelectionType::class,
                [
                    'label' => 'oro.shipping.product_shipping_options.product_unit.label',
                    'required' => false,
                ]
            )
            ->add(
                'weight',
                WeightType::class,
                [
                    'label' => 'oro.shipping.product_shipping_options.weight.label',
                    'required' => false,
                ]
            )
            ->add(
                'dimensions',
                DimensionsType::class,
                [
                    'label' => 'oro.shipping.product_shipping_options.dimensions.label',
                    'required' => false,
                ]
            )
            ->add(
                'freightClass',
                FreightClassSelectType::class,
                [
                    'label' => 'oro.shipping.product_shipping_options.freight_class.label',
                    'placeholder' => 'oro.shipping.form.placeholder.freight_class.label',
                    'attr' => [
                        'class' => 'freight-class-select',
                    ],
                    'required' => false,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'product' => null,
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
