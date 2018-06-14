<?php

namespace Oro\Bundle\VisibilityBundle\Form\Type;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductVisibilityType extends AbstractType
{
    const NAME = 'oro_visibility_product_default_visibility';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    'oro.visibility.product.visibility.visible.label' => ProductVisibility::VISIBLE,
                    'oro.visibility.product.visibility.hidden.label' => ProductVisibility::HIDDEN,
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
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
