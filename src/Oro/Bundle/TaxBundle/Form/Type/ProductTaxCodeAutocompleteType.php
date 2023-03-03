<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select a product tax code.
 */
class ProductTaxCodeAutocompleteType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_product_tax_code_autocomplete';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete_alias' => 'oro_product_tax_code',
            'grid_name'          => 'products-tax-code-select-grid',
            'create_form_route'  => 'oro_tax_product_tax_code_create'
        ]);
    }
}
