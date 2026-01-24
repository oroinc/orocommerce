<?php

namespace Oro\Bundle\VisibilityBundle\Form\Type;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting default category visibility in system configuration.
 *
 * This form type provides a choice field for administrators to set the default visibility for categories
 * (visible or hidden) in the system configuration UI.
 */
class CatalogVisibilityType extends AbstractType
{
    const NAME = 'oro_visibility_catalog_default_visibility';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    'oro.visibility.catalog.visibility.visible.label' => CategoryVisibility::VISIBLE,
                    'oro.visibility.catalog.visibility.hidden.label' => CategoryVisibility::HIDDEN,
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
