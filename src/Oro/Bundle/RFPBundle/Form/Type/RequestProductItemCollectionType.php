<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for managing a collection of request product items within a request product.
 *
 * This form type extends {@see CollectionType} to handle multiple {@see RequestProductItem} entries for
 * a single product in an RFP request. Each item represents a specific quantity/unit/price combination that the customer
 * is requesting a quote for (e.g., "10 pieces at $5 each" and "100 pieces at $4 each" for the same product).
 */
class RequestProductItemCollectionType extends AbstractType
{
    public const NAME = 'oro_rfp_request_product_item_collection';

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => RequestProductItemType::class,
            'show_form_when_empty'  => false,
            'error_bubbling'        => false,
            'prototype_name'        => '__namerequestproductitem__',
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
