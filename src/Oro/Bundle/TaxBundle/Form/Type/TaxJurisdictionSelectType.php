<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
