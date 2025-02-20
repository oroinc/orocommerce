<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The collection form type for QuoteProductOffer entity.
 */
class QuoteProductOfferCollectionType extends AbstractType
{
    public const string NAME = 'oro_sale_quote_product_offer_collection';

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type'           => QuoteProductOfferType::class,
            'show_form_when_empty' => false,
            'error_bubbling'       => false,
            'prototype_name'       => '__namequoteproductoffer__',
        ]);
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
