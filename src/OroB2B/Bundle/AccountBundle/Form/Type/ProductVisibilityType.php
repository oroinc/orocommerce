<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;

class ProductVisibilityType extends AbstractType
{
    const NAME = 'orob2b_account_product_default_visibility';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    ProductVisibility::VISIBLE => 'orob2b.account.product.visibility.visible.label',
                    ProductVisibility::HIDDEN => 'orob2b.account.product.visibility.hidden.label',
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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
