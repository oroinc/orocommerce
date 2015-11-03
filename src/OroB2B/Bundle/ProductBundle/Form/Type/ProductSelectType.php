<?php

namespace OroB2B\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

class ProductSelectType extends AbstractType
{
    const NAME = 'orob2b_product_select';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'orob2b_product',
                'create_form_route' => 'orob2b_product_create',
                'configs' => [
                    'placeholder' => 'orob2b.product.form.choose',
                    'result_template_twig' => 'OroB2BProductBundle:Product:Autocomplete/result.html.twig',
                    'selection_template_twig' => 'OroB2BProductBundle:Product:Autocomplete/selection.html.twig',
                ]
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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroEntitySelectOrCreateInlineType::NAME;
    }
}
