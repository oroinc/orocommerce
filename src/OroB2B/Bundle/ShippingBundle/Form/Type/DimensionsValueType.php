<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DimensionsValueType extends AbstractType
{
    const NAME = 'orob2b_shipping_dimensions_value';

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
                'length',
                'number',
                [
                    'attr' => ['class' => 'length'],
                    'required' => false,
                ]
            )
            ->add(
                'width',
                'number',
                [
                    'attr' => ['class' => 'width'],
                    'required' => false,
                ]
            )
            ->add(
                'height',
                'number',
                [
                    'attr' => ['class' => 'height'],
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
