<?php

namespace Oro\Bundle\TaxBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxSelectType extends AbstractType
{
    const NAME = 'oro_tax_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => 'oro_tax_autocomplete',
                'create_form_route' => 'oro_tax_create',
                'grid_name' => 'tax-taxes-select-grid',
                'configs' => [
                    'placeholder' => 'oro.tax.form.choose',
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
