<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for editing unit expressions in price rules.
 *
 * Extends the price rule editor text type to provide specialized handling for unit-specific
 * expressions without allowing mathematical operations.
 */
class RuleEditorUnitExpressionType extends PriceRuleEditorTextType
{
    public const NAME = 'oro_pricing_price_rule_editor_unit';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('allowedOperations', []);
    }
}
