<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class QuoteProductRequestCollectionType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_request_collection';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_collection';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'type'                  => QuoteProductRequestType::NAME,
            'show_form_when_empty'  => false,
            'prototype_name'        => '__namequoteproductrequest__',
            'allow_add'             => false,
            'allow_remove'          => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
