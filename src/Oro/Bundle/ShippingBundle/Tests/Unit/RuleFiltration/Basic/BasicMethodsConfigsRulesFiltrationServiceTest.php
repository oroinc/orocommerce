<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\RuleFiltration\Basic;

use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Converter\ShippingContextToRulesValuesConverterInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\RuleFiltration\Basic\BasicMethodsConfigsRulesFiltrationService;

class BasicMethodsConfigsRulesFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filtrationService;

    /**
     * @var ShippingContextToRulesValuesConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingContextToRuleValuesConverter;

    /**
     * @var BasicMethodsConfigsRulesFiltrationService
     */
    private $basicMethodsConfigsRulesFiltrationService;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->shippingContextToRuleValuesConverter = $this
            ->createMock(ShippingContextToRulesValuesConverterInterface::class);

        $this->basicMethodsConfigsRulesFiltrationService = new BasicMethodsConfigsRulesFiltrationService(
            $this->filtrationService,
            $this->shippingContextToRuleValuesConverter
        );
    }

    /**
     * {@inheritDoc}
     */
    public function testGetFilteredShippingMethodsConfigsRules()
    {
        $configRules = [
            $this->createShippingMethodsConfigsRule(),
            $this->createShippingMethodsConfigsRule(),
        ];
        $context = $this->createContextMock();
        $values = [
            'currency' => 'USD',
        ];

        $this->shippingContextToRuleValuesConverter->expects(static::once())
            ->method('convert')
            ->with($context)
            ->willReturn($values);

        $expectedConfigRules = [
            $this->createShippingMethodsConfigsRule(),
            $this->createShippingMethodsConfigsRule(),
        ];

        $this->filtrationService->expects(static::once())
            ->method('getFilteredRuleOwners')
            ->with($configRules, $values)
            ->willReturn($expectedConfigRules);

        static::assertEquals(
            $expectedConfigRules,
            $this->basicMethodsConfigsRulesFiltrationService->getFilteredShippingMethodsConfigsRules(
                $configRules,
                $context
            )
        );
    }

    /**
     * @return ShippingContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createContextMock()
    {
        return $this->createMock(ShippingContextInterface::class);
    }

    /**
     * @return ShippingMethodsConfigsRule|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createShippingMethodsConfigsRule()
    {
        return $this->createMock(ShippingMethodsConfigsRule::class);
    }
}
