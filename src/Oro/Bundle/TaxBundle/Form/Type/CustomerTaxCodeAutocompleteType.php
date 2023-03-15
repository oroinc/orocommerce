<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type to select a customer tax code.
 */
class CustomerTaxCodeAutocompleteType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_customer_tax_code_autocomplete';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete_alias' => 'oro_customer_tax_code',
            'grid_name'          => 'customers-tax-code-select-grid',
            'create_form_route'  => 'oro_tax_customer_tax_code_create'
        ]);
    }
}
