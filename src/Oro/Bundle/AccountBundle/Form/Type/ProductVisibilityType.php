<?php

namespace Oro\Bundle\AccountBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;

class ProductVisibilityType extends AbstractType
{
    const NAME = 'oro_account_product_default_visibility';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    ProductVisibility::VISIBLE => 'oro.account.product.visibility.visible.label',
                    ProductVisibility::HIDDEN => 'oro.account.product.visibility.hidden.label',
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
