<?php

namespace Oro\Bundle\VisibilityBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;

class CatalogVisibilityType extends AbstractType
{
    const NAME = 'oro_visibility_catalog_default_visibility';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    CategoryVisibility::VISIBLE => 'oro.visibility.catalog.visibility.visible.label',
                    CategoryVisibility::HIDDEN => 'oro.visibility.catalog.visibility.hidden.label',
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
