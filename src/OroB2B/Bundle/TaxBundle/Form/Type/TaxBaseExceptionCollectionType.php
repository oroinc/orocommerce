<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class TaxBaseExceptionCollectionType extends AbstractType
{
    const NAME = 'orob2b_tax_base_exception_collection';

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
                'type' => TaxBaseExceptionType::NAME,
                'show_form_when_empty' => false
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