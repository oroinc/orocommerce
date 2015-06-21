<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProductDefaultVisibilityType extends AbstractType
{
    const NAME = 'orob2b_product_default_visibility';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    Product::VISIBILITY_VISIBLE => 'orob2b.product.visibility.visible.label',
                    Product::VISIBILITY_NOT_VISIBLE => 'orob2b.product.visibility.not_visible.label',
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
