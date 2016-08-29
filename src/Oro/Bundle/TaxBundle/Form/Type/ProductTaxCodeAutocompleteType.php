<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

class ProductTaxCodeAutocompleteType extends AbstractType
{
    const NAME = 'orob2b_product_tax_code_autocomplete';
    const AUTOCOMPLETE_ALIAS = 'orob2b_product_tax_code';

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

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return OroEntitySelectOrCreateInlineType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => self::AUTOCOMPLETE_ALIAS,
                'grid_name' => 'products-tax-code-select-grid',
                'create_form_route' => 'orob2b_tax_product_tax_code_create',
            ]
        );
    }
}
