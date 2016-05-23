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
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'value',
                DimensionsValueType::NAME,
                [
                    'required' => false,
                ]
            )
            ->add(
                'unit',
                LengthUnitSelectType::NAME,
                [
                    'placeholder' => 'orob2b.shipping.form.placeholder.length_unit.label',
                    'required' => false,
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
