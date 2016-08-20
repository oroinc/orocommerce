<?php

namespace Oro\Bundle\RFPBundle\Form\Type\Frontend;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\RFPBundle\Form\Type\RequestProductItemType;

class RequestProductItemCollectionType extends AbstractType
{
    const NAME = 'orob2b_rfp_frontend_request_product_item_collection';

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
            'type' => RequestProductItemType::NAME,
            'show_form_when_empty'  => false,
            'error_bubbling'        => false,
            'prototype_name'        => '__namerequestproductitem__',
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
