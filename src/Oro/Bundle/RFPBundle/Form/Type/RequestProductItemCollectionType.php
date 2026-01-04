<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestProductItemCollectionType extends AbstractType
{
    public const NAME = 'oro_rfp_request_product_item_collection';

    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entry_type' => RequestProductItemType::class,
            'show_form_when_empty'  => false,
            'error_bubbling'        => false,
            'prototype_name'        => '__namerequestproductitem__',
        ]);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
