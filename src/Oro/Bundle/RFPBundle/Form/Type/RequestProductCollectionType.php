<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing a collection of request products in an RFP request.
 *
 * This form type extends {@see CollectionType} to handle multiple {@see RequestProduct} entries
 * within a Request for Quote. Each entry represents a product line item that the customer is requesting a quote for,
 * including product selection, quantities, units, and optional comments.
 */
class RequestProductCollectionType extends AbstractType
{
    public const NAME = 'oro_rfp_request_product_collection';

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => RequestProductType::class,
            'show_form_when_empty'  => true,
            'error_bubbling'        => false,
            'prototype_name'        => '__namerequestproduct__',
        ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
