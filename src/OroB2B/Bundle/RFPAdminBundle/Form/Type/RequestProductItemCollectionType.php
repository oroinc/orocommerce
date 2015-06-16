<?php

namespace OroB2B\Bundle\RFPAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class RequestProductItemCollectionType extends AbstractType
{
    const NAME = 'orob2b_rfp_admin_request_product_item_collection';

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
            'type'  => RequestProductItemType::NAME,
            'show_form_when_empty'  => false,
            'prototype_name'        => '__namerequestproductitem__',
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
