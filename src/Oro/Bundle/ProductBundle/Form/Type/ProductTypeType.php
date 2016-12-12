<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductTypeType extends AbstractType
{
    const NAME = 'oro_product_type';

    /**
     * @var ProductTypeProvider
     */
    private $provider;

    /**
     * @param ProductTypeProvider $provider
     */
    public function __construct(ProductTypeProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'choices' => $this->provider->getAvailableProductTypes(),
            'preferred_choices' => Product::TYPE_SIMPLE
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
