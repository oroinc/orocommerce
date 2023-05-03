<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PricingBundle\Form\OptionsConfigurator\PriceRuleEditorOptionsConfigurator;
use Oro\Bundle\PricingBundle\Form\Type\RuleEditorCurrencyExpressionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RuleEditorCurrencyExpressionTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var PriceRuleEditorOptionsConfigurator|\PHPUnit\Framework\MockObject\MockObject */
    private $configurator;

    /** @var RuleEditorCurrencyExpressionType */
    private $type;

    protected function setUp(): void
    {
        $this->configurator = $this->createMock(PriceRuleEditorOptionsConfigurator::class);

        $this->type = new RuleEditorCurrencyExpressionType($this->configurator);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $this->configurator->expects($this->once())
            ->method('configureOptions')
            ->with($resolver);
        $resolver->expects($this->once())
            ->method('setDefault')
            ->with('allowedOperations', []);

        $this->type->configureOptions($resolver);
    }
}
