<?php

namespace OroB2B\Bundle\InvoiceBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

/**
 * {@inheritdoc}
 */
class InvoiceLineItemsCollectionType extends AbstractType
{
    const NAME = 'orob2b_invoice_line_items_collection';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => InvoiceLineItemType::NAME,
            'show_form_when_empty' => true,
            'error_bubbling' => false,
            'prototype_name' => '__nameinvoicelineitem__'
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
