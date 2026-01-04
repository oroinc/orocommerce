<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Builds a group of fields: length, width, height.
 */
class DimensionsValueType extends AbstractType
{
    public const NAME = 'oro_shipping_dimensions_value';

    /** @var string */
    protected $dataClass;

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'length',
                CommonUnitValueType::class,
                [
                    'attr' => ['class' => 'length'],
                    'required' => false,
                ]
            )
            ->add(
                'width',
                CommonUnitValueType::class,
                [
                    'attr' => ['class' => 'width'],
                    'required' => false,
                ]
            )
            ->add(
                'height',
                CommonUnitValueType::class,
                [
                    'attr' => ['class' => 'height'],
                    'required' => false,
                ]
            );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass
            ]
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
