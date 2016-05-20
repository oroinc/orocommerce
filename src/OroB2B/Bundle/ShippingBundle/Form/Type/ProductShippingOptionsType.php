<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;

class ProductShippingOptionsType extends AbstractType
{
    const NAME = 'orob2b_shipping_product_shipping_options';

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
                ProductUnitSelectionType::NAME,
                [
                    'label' => 'orob2b.shipping.product_shipping_options.product_unit.label',
                    'required' => false,
                ]
            )
            ->add(
                'weight',
                WeightType::NAME,
                [
                    'label' => 'orob2b.shipping.product_shipping_options.weight.label',
                    'required' => false,
                ]
            )
            ->add(
                'dimensions',
                DimensionsType::NAME,
                [
                    'label' => 'orob2b.shipping.product_shipping_options.dimensions.label',
                    'required' => false,
                ]
            )
            ->add(
                'freightClass',
                FreightClassSelectType::NAME,
                [
                    'label' => 'orob2b.shipping.product_shipping_options.freight_class.label',
                    'placeholder' => 'orob2b.shipping.form.placeholder.freight_class.label',
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
        return self::NAME;
    }
}
