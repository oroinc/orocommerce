<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

use Oro\Bundle\CustomerBundle\Entity\Visibility\CategoryVisibility;

class CatalogVisibilityType extends AbstractType
{
    const NAME = 'oro_account_catalog_default_visibility';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    CategoryVisibility::VISIBLE => 'oro.customer.catalog.visibility.visible.label',
                    CategoryVisibility::HIDDEN => 'oro.customer.catalog.visibility.hidden.label',
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
