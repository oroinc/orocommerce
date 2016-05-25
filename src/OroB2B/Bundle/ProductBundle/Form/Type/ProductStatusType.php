<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Provider\ProductStatusProvider;

class ProductStatusType extends AbstractType
{
    const NAME = 'orob2b_product_status';

    /**
     * @var  ProductStatusProvider $productStatuses
     */
    protected $productStatusProvider;

    /**
     * @param ProductStatusProvider $productStatusProvider
     */
    public function __construct(ProductStatusProvider $productStatusProvider)
    {
        $this->productStatusProvider = $productStatusProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->productStatusProvider->getAvailableProductStatuses(),
            'preferred_choices' => Product::STATUS_DISABLED
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'choice';
    }
}
