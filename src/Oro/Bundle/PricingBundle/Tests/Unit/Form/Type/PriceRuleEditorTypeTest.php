<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FrontendBundle\Form\Type\RuleEditorTextareaType;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\OptionsResolver;
use Oro\Bundle\PricingBundle\Form\OptionsConfigurator\PriceRuleEditorOptionsConfigurator;
use Oro\Bundle\PricingBundle\Form\Type\PriceRuleEditorType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PriceRuleEditorTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceRuleEditorOptionsConfigurator|\PHPUnit\Framework\MockObject\MockObject */
    private $optionsConfigurator;

    /** @var PriceRuleEditorType */
    private $type;

    protected function setUp(): void
    {
        $this->optionsConfigurator = $this->createMock(PriceRuleEditorOptionsConfigurator::class);

        $this->type = new PriceRuleEditorType($this->optionsConfigurator);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(PriceRuleEditorType::NAME, $this->type->getBlockPrefix());
    }

    public function testGetParent()
    {
        $this->assertEquals(RuleEditorTextareaType::class, $this->type->getParent());
    }

    public function testFinishView()
    {
        $form = $this->createMock(FormInterface::class);
        $view = new FormView();

        $this->optionsConfigurator->expects($this->once())
            ->method('limitNumericOnlyRules')
            ->with($view, []);

        $this->type->finishView($view, $form, []);
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
