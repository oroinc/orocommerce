<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting a price list with inline creation support.
 *
 * Provides an autocomplete field for selecting existing price lists with the ability
 * to create new price lists inline.
 */
class PriceListSelectType extends AbstractType
{
    const NAME = 'oro_pricing_price_list_select';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'autocomplete_alias' => PriceListType::class,
                'create_form_route' => 'oro_pricing_price_list_create',
                'configs' => [
                    'placeholder' => 'oro.pricing.form.choose_price_list'
                ]
            ]
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return OroEntitySelectOrCreateInlineType::class;
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return static::NAME;
    }
}
