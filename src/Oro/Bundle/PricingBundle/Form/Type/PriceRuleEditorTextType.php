<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\FrontendBundle\Form\Type\RuleEditorTextType;
use Oro\Bundle\PricingBundle\Form\OptionsConfigurator\PriceRuleEditorOptionsConfigurator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for editing price rules as text with expression support.
 *
 * Provides a text-based editor for price rule expressions with configurable options
 * and validation, supporting inline rule editing.
 */
class PriceRuleEditorTextType extends AbstractType
{
    const NAME = 'oro_pricing_price_rule_editor_text';

    /**
     * @var PriceRuleEditorOptionsConfigurator
     */
    private $optionsConfigurator;

    public function __construct(PriceRuleEditorOptionsConfigurator $optionsConfigurator)
    {
        $this->optionsConfigurator = $optionsConfigurator;
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $this->optionsConfigurator->configureOptions($resolver);
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $this->optionsConfigurator->limitNumericOnlyRules($view, $options);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return RuleEditorTextType::class;
    }
}
