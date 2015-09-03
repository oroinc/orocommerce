<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type\Frontend;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class RequestProductCollectionType extends AbstractType
{
    const NAME = 'orob2b_rfp_frontend_request_product_collection';

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
            'type' => RequestProductType::NAME,
            'show_form_when_empty' => false,
            'error_bubbling' => false,
            'prototype_name' => '__namerequestproduct__',
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
