<?php

namespace Oro\Bundle\AccountBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;

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
                    CategoryVisibility::VISIBLE => 'oro.account.catalog.visibility.visible.label',
                    CategoryVisibility::HIDDEN => 'oro.account.catalog.visibility.hidden.label',
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
