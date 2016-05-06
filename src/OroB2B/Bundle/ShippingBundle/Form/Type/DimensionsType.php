<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ShippingBundle\Form\DataTransformer\DimensionsTransformer;
use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

class DimensionsType extends AbstractType
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
                'entity',
                [
                    'class' => $this->provider->getEntityClass(),
                    'choices' => $this->provider->getUnits(),
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
