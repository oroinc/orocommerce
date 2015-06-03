<?php

namespace OroB2B\Bundle\SaleBundle\Form\Type;

use OroB2B\Bundle\SaleBundle\Validator\Constraints\QuoteProductItems;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class QuoteProductItemCollectionType extends AbstractType
{
    const NAME = 'orob2b_sale_quote_product_item_collection';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_collection';
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'type' => QuoteProductItemType::NAME,
            'show_form_when_empty' => false,
            'prototype_name'       => '__namequoteproductitem__'
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
