<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteProductCollectionType extends AbstractType
{
    const NAME = 'oro_sale_quote_product_collection';

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type'                  => QuoteProductType::class,
            'show_form_when_empty'  => true,
            'error_bubbling'        => false,
            'prototype_name'        => '__namequoteproduct__'
        ]);
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
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
