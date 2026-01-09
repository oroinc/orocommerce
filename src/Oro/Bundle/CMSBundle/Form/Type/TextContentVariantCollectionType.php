<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing text content variant collections.
 *
 * Extends the base CollectionType to provide specialized handling for text content variants,
 * allowing users to manage multiple text content variants with a consistent interface.
 */
class TextContentVariantCollectionType extends AbstractType
{
    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'entry_type' => TextContentVariantType::class,
                'prototype_name' => '__variant_idx__',
            ]
        );
    }
}
