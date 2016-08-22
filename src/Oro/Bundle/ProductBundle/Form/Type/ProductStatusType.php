<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductStatusProvider;

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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
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
