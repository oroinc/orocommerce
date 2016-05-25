<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class ProductUnitsType extends AbstractType
{
    const NAME = 'orob2b_product_units';

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
            'choices' => $this->productUnitsProvider->getAvailableProductUnits(),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
