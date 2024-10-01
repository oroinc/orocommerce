<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\ShippingBundle\Form\DataTransformer\DimensionsTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DimensionsType extends AbstractType
{
    const NAME = 'oro_shipping_dimensions';

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
                'value',
                DimensionsValueType::class,
                [
                    'required' => false,
                ]
            )
            ->add(
                'unit',
                LengthUnitSelectType::class,
                [
                    'placeholder' => 'oro.shipping.form.placeholder.length_unit.label',
                    'required' => false,
                ]
            );

        $builder->addViewTransformer(new DimensionsTransformer());
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
