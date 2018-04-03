<?php

namespace Oro\Bundle\SaleBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteProductRequestCollectionType extends AbstractType
{
    const NAME = 'oro_sale_quote_product_request_collection';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type'            => QuoteProductRequestType::class,
            'show_form_when_empty'  => false,
            'prototype_name'        => '__namequoteproductrequest__',
            'allow_add'             => false,
            'allow_delete'          => false,
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
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
