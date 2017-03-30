<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class TextContentVariantCollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => TextContentVariantType::class,
                'prototype_name' => '__variant_idx__',
            ]
        );
    }
}
