<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductUnitsType extends AbstractType
{
    const NAME = 'oro_product_units';

    /**
     * @var  ProductUnitsProvider
     */
    protected $productUnitsProvider;

    /**
     * @param ProductUnitsProvider $productUnitsProvider
     */
    public function __construct(ProductUnitsProvider $productUnitsProvider)
    {
        $this->productUnitsProvider = $productUnitsProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            // TODO: remove 'choices_as_values' option below in scope of BAP-15236
            'choices_as_values' => true,
            'choices' => $this->productUnitsProvider->getAvailableProductUnits(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
