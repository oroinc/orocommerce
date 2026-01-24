<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting or creating tax jurisdictions.
 *
 * This form type provides a select widget with autocomplete functionality for choosing existing tax jurisdictions,
 * along with the ability to create new jurisdictions inline.
 * It is used in tax rule forms to specify the geographic area where a tax rule applies.
 */
class TaxJurisdictionSelectType extends AbstractType
{
    const NAME = 'oro_tax_jurisdiction_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_tax_jurisdiction_autocomplete',
                'create_form_route' => 'oro_tax_jurisdiction_create',
                'grid_name' => 'tax-jurisdiction-select-grid',
                'configs' => [
                    'placeholder' => 'oro.tax.taxjurisdiction.form.choose',
                ],
            ]
        );
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

    #[\Override]
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }
}
