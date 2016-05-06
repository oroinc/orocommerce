<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

class ProductShippingOptionsType extends AbstractType
{
    /**
     * @var AbstractMeasureUnitProvider
     */
    protected $freightClassesProvider;

    /**
     * @param AbstractMeasureUnitProvider $freightClassesProvider
     */
    public function __construct(AbstractMeasureUnitProvider $freightClassesProvider)
    {
        $this->freightClassesProvider = $freightClassesProvider;
    }

    const NAME = 'orob2b_shipping_product_shipping_options';

    /**
     * @var string
     */
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
                ]
            )
            ->add(
                'weight',
                WeightType::NAME,
                [
                    'label' => 'orob2b.shipping.product_shipping_options.weight.label',
                ]
            )
            ->add(
                'dimensions',
                DimensionsType::NAME,
                [
                    'label' => 'orob2b.shipping.product_shipping_options.dimensions.label',
                ]
            )
            ->add(
                'freightClass',
                'entity',
                [
                    'class' => $this->freightClassesProvider->getEntityClass(),
                    'choices' => $this->freightClassesProvider->getUnits(),
                    'label' => 'orob2b.shipping.product_shipping_options.freight_class.label',
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
                'data_class' => $this->dataClass,
                'intention' => 'product_shipping_options',
                'by_reference' => false,
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
