<?php

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type that represents an RFP request product collection.
 */
class RequestProductCollectionType extends AbstractType
{
    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => RequestProductType::class,
            'show_form_when_empty' => false,
            'error_bubbling' => false,
            'prototype_name' => '__namerequestproduct__',
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_rfp_frontend_request_product_collection';
    }
}
