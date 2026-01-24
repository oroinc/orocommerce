<?php

namespace Oro\Bundle\VisibilityBundle\Form\Type;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting default product visibility in system configuration.
 *
 * This form type provides a choice field for administrators to set the default visibility for products
 * (visible or hidden) in the system configuration UI.
 */
class ProductVisibilityType extends AbstractType
{
    const NAME = 'oro_visibility_product_default_visibility';

    #[\Override]
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

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
