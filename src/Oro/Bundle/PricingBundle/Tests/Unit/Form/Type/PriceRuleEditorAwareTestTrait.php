<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FrontendBundle\Form\OptionsConfigurator\RuleEditorOptionsConfigurator;
use Oro\Bundle\FrontendBundle\Form\Type\RuleEditorTextareaType;
use Oro\Bundle\FrontendBundle\Form\Type\RuleEditorTextType;
use Oro\Bundle\PricingBundle\Form\OptionsConfigurator\PriceRuleEditorOptionsConfigurator;
use Oro\Bundle\PricingBundle\Form\Type\PriceRuleEditorTextType;
use Oro\Bundle\PricingBundle\Form\Type\PriceRuleEditorType;
use Oro\Bundle\PricingBundle\Form\Type\RuleEditorCurrencyExpressionType;
use Oro\Bundle\PricingBundle\Form\Type\RuleEditorUnitExpressionType;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;

trait PriceRuleEditorAwareTestTrait
{
    private function getPriceRuleEditorExtension(): array
    {
        $autocompleteFieldsProvider = $this->createMock(AutocompleteFieldsProvider::class);
        $priceListSelectFormView = $this->createMock(FormView::class);
        $priceListSelectForm = $this->createMock(FormInterface::class);
        $priceListSelectForm->expects($this->any())
            ->method('createView')
            ->willReturn($priceListSelectFormView);
        $formFactory = $this->createMock(FormFactoryInterface::class);
        $formFactory->expects($this->any())
            ->method('createNamed')
            ->willReturn($priceListSelectForm);
        $twig = $this->createMock(Environment::class);
        $priceRuleOptionsConfigurator = new PriceRuleEditorOptionsConfigurator(
            $autocompleteFieldsProvider,
            $formFactory,
            $twig
        );
        $formatter = $this->createMock(UnitLabelFormatter::class);
        $priceRuleEditor = new PriceRuleEditorType($priceRuleOptionsConfigurator);
        $priceRuleEditorText = new PriceRuleEditorTextType($priceRuleOptionsConfigurator);

        $ruleEditorOptionsConfigurator = new RuleEditorOptionsConfigurator();
        $ruleEditor = new RuleEditorTextareaType($ruleEditorOptionsConfigurator);
        $ruleEditorText = new RuleEditorTextType($ruleEditorOptionsConfigurator);

        $currencyExpressionType = new RuleEditorCurrencyExpressionType($priceRuleOptionsConfigurator);
        $unitExpressionType = new RuleEditorUnitExpressionType($priceRuleOptionsConfigurator);

        return [
            PriceRuleEditorType::NAME => $priceRuleEditor,
            PriceRuleEditorTextType::NAME => $priceRuleEditorText,
            RuleEditorTextareaType::NAME => $ruleEditor,
            RuleEditorTextType::NAME => $ruleEditorText,
            RuleEditorCurrencyExpressionType::NAME => $currencyExpressionType,
            RuleEditorUnitExpressionType::NAME => $unitExpressionType,
            ProductUnitSelectType::NAME => new ProductUnitSelectType($formatter),
        ];
    }
}
