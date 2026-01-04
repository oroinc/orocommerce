<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

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
