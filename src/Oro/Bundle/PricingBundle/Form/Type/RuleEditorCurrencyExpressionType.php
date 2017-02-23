<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

class RuleEditorCurrencyExpressionType extends PriceRuleEditorTextType
{
    const NAME = 'oro_pricing_price_rule_editor_currency';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('allowedOperations', []);
    }
}
