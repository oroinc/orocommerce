<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

use OroB2B\Bundle\AccountBundle\Entity\CategoryVisibility;

class CatalogVisibilityType extends AbstractType
{
    const NAME = 'orob2b_account_catalog_default_visibility';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    CategoryVisibility::VISIBLE => 'orob2b.account.catalog.visibility.visible.label',
                    CategoryVisibility::HIDDEN => 'orob2b.account.catalog.visibility.hidden.label',
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
