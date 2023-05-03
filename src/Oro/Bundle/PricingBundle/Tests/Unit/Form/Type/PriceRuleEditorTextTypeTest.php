<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FrontendBundle\Form\Type\RuleEditorTextType;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PricingBundle\Form\OptionsConfigurator\PriceRuleEditorOptionsConfigurator;
use Oro\Bundle\PricingBundle\Form\Type\PriceRuleEditorTextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PriceRuleEditorTextTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceRuleEditorOptionsConfigurator|\PHPUnit\Framework\MockObject\MockObject */
    private $optionsConfigurator;

    /** @var PriceRuleEditorTextType */
    private $type;

    protected function setUp(): void
    {
        $this->optionsConfigurator = $this->createMock(PriceRuleEditorOptionsConfigurator::class);

        $this->type = new PriceRuleEditorTextType($this->optionsConfigurator);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PriceRuleEditorTextType::NAME, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(RuleEditorTextType::class, $this->type->getParent());
    }

    public function testFinishView()
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = [];

        $this->optionsConfigurator->expects($this->once())
            ->method('limitNumericOnlyRules')
            ->with($view, $options);

        $this->type->finishView($view, $form, $options);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();

        $this->optionsConfigurator->expects($this->once())
            ->method('configureOptions')
            ->with($resolver);

        $this->type->configureOptions($resolver);
    }
}
