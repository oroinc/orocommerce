<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for editing currency expressions in price rules.
 *
 * Extends the price rule editor text type to provide specialized handling for currency-specific
 * expressions without allowing mathematical operations.
 */
class RuleEditorCurrencyExpressionType extends PriceRuleEditorTextType
{
    const NAME = 'oro_pricing_price_rule_editor_currency';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('allowedOperations', []);
    }
}
