<?php

namespace OroB2B\Bundle\AccountBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CatalogVisibilityType extends AbstractType
{
    const NAME = 'orob2b_account_catalog_default_visibility';
    const VISIBILITY_VISIBLE = 'visible';
    const VISIBILITY_NOT_VISIBLE = 'not_visible';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'choices' => [
                    self::VISIBILITY_VISIBLE => 'orob2b.account.catalog.visibility.visible.label',
                    self::VISIBILITY_NOT_VISIBLE => 'orob2b.account.catalog.visibility.not_visible.label',
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
