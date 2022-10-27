<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PricingBundle\Form\Type\PriceListStrategySelectType;
use Oro\Bundle\PricingBundle\PricingStrategy\StrategyRegister;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PriceListStrategySelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|StrategyRegister */
    private $strategyRegister;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TranslatorInterface */
    private $translator;

    /** @var PriceListStrategySelectType */
    private $type;

    protected function setUp(): void
    {
        $this->strategyRegister = $this->createMock(StrategyRegister::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->type = new PriceListStrategySelectType($this->strategyRegister, $this->translator);
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
            PriceListStrategySelectType::ALIAS.'merge_by_priority' => 'merge_by_priority',
            PriceListStrategySelectType::ALIAS.'test_strategy' => 'test_strategy',
        ];

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);
        $this->strategyRegister->expects($this->once())
            ->method('getStrategies')
            ->willReturn($strategies);

        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['choices' => $expectedChoices]);

        $this->type->configureOptions($resolver);
    }
}
