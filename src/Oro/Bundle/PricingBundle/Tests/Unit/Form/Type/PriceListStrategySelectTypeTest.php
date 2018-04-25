<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PricingBundle\Form\Type\PriceListStrategySelectType;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class PriceListStrategySelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|StrategyRegister
     */
    protected $strategyRegister;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var PriceListStrategySelectType
     */
    protected $type;

    protected function setUp()
    {
        $this->strategyRegister = $this->getMockBuilder(StrategyRegister::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->type = new PriceListStrategySelectType($this->strategyRegister, $this->translator);
    }

    protected function tearDown()
    {
        unset($this->type, $this->strategyRegister, $this->translator);
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }

    public function testConfigureOptions()
    {
        $strategies = [
            'merge_by_priority' => 'strategy1',
            'test_strategy' => 'strategy2'
        ];

        $expectedChoices = [
            'merge_by_priority' => PriceListStrategySelectType::ALIAS.'merge_by_priority',
            'test_strategy' => PriceListStrategySelectType::ALIAS.'test_strategy'
        ];

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($message) {
                    return $message;
                }
            );
        $this->strategyRegister->expects($this->once())
            ->method('getStrategies')
            ->will($this->returnValue($strategies));

        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMockBuilder(OptionsResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefault')
            ->with('choices', $expectedChoices);

        $this->type->configureOptions($resolver);
    }
}
