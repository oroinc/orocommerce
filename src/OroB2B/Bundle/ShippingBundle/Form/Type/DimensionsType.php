<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ShippingBundle\Form\DataTransformer\DimensionsTransformer;

class DimensionsType extends AbstractType
{
    const NAME = 'orob2b_shipping_dimensions';

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
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('length', 'number', ['attr' => ['class' => 'length freight-class-update-trigger']])
            ->add('width', 'number', ['attr' => ['class' => 'width freight-class-update-trigger']])
            ->add('height', 'number', ['attr' => ['class' => 'height freight-class-update-trigger']])
            ->add(
                'unit',
                LengthUnitSelectType::NAME,
                [
                    'placeholder' => 'orob2b.shipping.form.placeholder.length_unit.label',
                    'attr' => ['class' => 'freight-class-update-trigger'],
                ]
            );

        $builder->addViewTransformer(new DimensionsTransformer());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'error_bubbling' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
