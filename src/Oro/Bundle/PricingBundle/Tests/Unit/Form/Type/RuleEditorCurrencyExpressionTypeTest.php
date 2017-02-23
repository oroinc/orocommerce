<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PricingBundle\Form\OptionsConfigurator\PriceRuleEditorOptionsConfigurator;
use Oro\Bundle\PricingBundle\Form\Type\RuleEditorCurrencyExpressionType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RuleEditorCurrencyExpressionTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceRuleEditorOptionsConfigurator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configurator;

    /**
     * @var RuleEditorCurrencyExpressionType
     */
    private $type;

    protected function setUp()
    {
        $this->configurator = $this->getMockBuilder(PriceRuleEditorOptionsConfigurator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new RuleEditorCurrencyExpressionType($this->configurator);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->getMockBuilder(OptionsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->configurator->expects($this->once())
            ->method('configureOptions')
            ->with($resolver);
        $resolver->expects($this->once())
            ->method('setDefault')
            ->with('allowedOperations', []);

        $this->type->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(RuleEditorCurrencyExpressionType::NAME, $this->type->getName());
    }
}
