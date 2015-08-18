<?php

namespace OroB2B\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class OrderLineItemsCollectionType extends AbstractType
{
    const NAME = 'orob2b_order_line_items_collection';

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
        $resolver->setDefaults(
            [
                'type' => OrderLineItemType::NAME,
                'show_form_when_empty' => false,
                'error_bubbling' => false,
                'cascade_validation' => true,
                'prototype_name' => '__nameorderlineitem__',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
