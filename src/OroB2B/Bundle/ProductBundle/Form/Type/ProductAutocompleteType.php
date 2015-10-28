<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroAutocompleteType;

class ProductAutocompleteType extends AbstractType
{
    const NAME = 'orob2b_product_autocomplete';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroAutocompleteType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete' => [
                    'alias' => 'orob2b_product',
                    'selection_template_twig' =>
                        'OroB2BProductBundle:Product:Autocomplete/autocomplete_selection.html.twig',
                    'componentModule' => 'orob2bproduct/js/app/components/product-autocomplete-component',
                ],
            ]
        );
    }
}
