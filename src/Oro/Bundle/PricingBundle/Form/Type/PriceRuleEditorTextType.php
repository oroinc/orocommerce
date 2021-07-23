<?php

namespace Oro\Bundle\PricingBundle\Form\Type;

use Oro\Bundle\FrontendBundle\Form\Type\RuleEditorTextType;
use Oro\Bundle\PricingBundle\Form\OptionsConfigurator\PriceRuleEditorOptionsConfigurator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $this->optionsConfigurator->configureOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $this->optionsConfigurator->limitNumericOnlyRules($view, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return RuleEditorTextType::class;
    }
}
