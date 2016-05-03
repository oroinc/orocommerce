<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use OroB2B\Bundle\ShippingBundle\Provider\ShippingOptionsProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ShippingBundle\Form\DataTransformer\DimensionsTransformer;

class DimensionsType extends AbstractType
{
    /**
     * @var ShippingOptionsProvider
     */
    protected $provider;

    public function __construct(ShippingOptionsProvider $provider)
    {
        $this->provider = $provider;
    }

    const NAME = 'orob2b_shipping_dimensions';

    /** @var string */
    protected $dataClass;

    /** @var string */
    protected $entityClass = 'OroB2B\Bundle\ShippingBundle\Entity\LengthUnit';

    /**
     * @param $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

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
            ->add('length', 'number', ['attr' => ['class' => 'length',],])
            ->add('width', 'number', ['attr' => ['class' => 'width',],])
            ->add('height', 'number', ['attr' => ['class' => 'height',],])
            ->add('unit', 'entity', ['class' => $this->entityClass, 'choices' => $this->provider->getLengthUnits()]);

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
