<?php

namespace OroB2B\Bundle\TaxBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;

class AccountTaxCodeAutocompleteType extends AbstractType
{
    const NAME = 'orob2b_account_tax_code_autocomplete';

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
                'autocomplete_alias' => 'orob2b_account_tax_code',
                'grid_name' => 'accounts-tax-code-select-grid',
                'create_form_route' => 'orob2b_tax_account_tax_code_create',
            ]
        );
    }
}
