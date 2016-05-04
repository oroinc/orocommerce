<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ShippingBundle\Form\DataTransformer\WeightTransformer;
use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

class WeightType extends AbstractType
{
    /**
     * @var AbstractMeasureUnitProvider
     */
    protected $provider;

    /**
     * @param AbstractMeasureUnitProvider $provider
     */
    public function __construct(AbstractMeasureUnitProvider $provider)
    {
        $this->provider = $provider;
    }

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
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('value', 'number', ['attr' => ['class' => 'value',],])
            ->add(
                'unit',
                'entity',
                ['class' => $this->provider->getEntityClass(), 'choices' => $this->provider->getUnits()]
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
                'data_class' => $this->dataClass,
                'compact' => false
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
