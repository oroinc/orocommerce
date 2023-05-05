<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\RuleFiltration\Basic;

use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Converter\ShippingContextToRulesValuesConverterInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\RuleFiltration\Basic\BasicMethodsConfigsRulesFiltrationService;

class BasicMethodsConfigsRulesFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtrationService;

    /** @var ShippingContextToRulesValuesConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingContextToRuleValuesConverter;

    /** @var BasicMethodsConfigsRulesFiltrationService */
    private $basicMethodsConfigsRulesFiltrationService;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->shippingContextToRuleValuesConverter = $this->createMock(
            ShippingContextToRulesValuesConverterInterface::class
        );

        $this->basicMethodsConfigsRulesFiltrationService = new BasicMethodsConfigsRulesFiltrationService(
            $this->filtrationService,
            $this->shippingContextToRuleValuesConverter
        );
    }

    public function testGetFilteredShippingMethodsConfigsRules()
    {
        $configRules = [
            $this->createMock(ShippingMethodsConfigsRule::class),
            $this->createMock(ShippingMethodsConfigsRule::class),
        ];
        $context = $this->createMock(ShippingContextInterface::class);
        $values = [
            'currency' => 'USD',
        ];

        $this->shippingContextToRuleValuesConverter->expects(self::once())
            ->method('convert')
            ->with($context)
            ->willReturn($values);

        $expectedConfigRules = [
            $this->createMock(ShippingMethodsConfigsRule::class),
            $this->createMock(ShippingMethodsConfigsRule::class),
        ];

        $this->filtrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($configRules, $values)
            ->willReturn($expectedConfigRules);

        self::assertEquals(
            $expectedConfigRules,
            $this->basicMethodsConfigsRulesFiltrationService->getFilteredShippingMethodsConfigsRules(
                $configRules,
                $context
            )
        );
    }
}
