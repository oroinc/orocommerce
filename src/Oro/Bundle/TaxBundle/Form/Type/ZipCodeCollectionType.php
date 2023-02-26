<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type for the collection of tax jurisdiction zip codes.
 */
class ZipCodeCollectionType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => ZipCodeType::class,
            'required'   => false
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_tax_zip_code_collection_type';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return CollectionType::class;
    }
}
