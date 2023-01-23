<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\RuleFiltration\Basic;

use Oro\Bundle\PaymentBundle\Context\Converter\PaymentContextToRulesValueConverterInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\RuleFiltration\Basic\BasicMethodsConfigsRulesFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class BasicMethodsConfigsRulesFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /** @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $filtrationService;

    /** @var PaymentContextToRulesValueConverterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentContextToRuleValuesConverter;

    /** @var BasicMethodsConfigsRulesFiltrationService */
    private $basicMethodsConfigsRulesFiltrationService;

    protected function setUp(): void
    {
        $this->filtrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $this->paymentContextToRuleValuesConverter = $this->createMock(
            PaymentContextToRulesValueConverterInterface::class
        );

        $this->basicMethodsConfigsRulesFiltrationService = new BasicMethodsConfigsRulesFiltrationService(
            $this->filtrationService,
            $this->paymentContextToRuleValuesConverter
        );
    }

    public function testGetFilteredPaymentMethodsConfigsRules()
    {
        $configRules = [
            $this->createMock(PaymentMethodsConfigsRule::class),
            $this->createMock(PaymentMethodsConfigsRule::class),
        ];
        $context = $this->createMock(PaymentContextInterface::class);
        $values = [
            'currency' => 'USD',
        ];

        $this->paymentContextToRuleValuesConverter->expects(self::once())
            ->method('convert')
            ->with($context)
            ->willReturn($values);

        $expectedConfigRules = [
            $this->createMock(PaymentMethodsConfigsRule::class),
            $this->createMock(PaymentMethodsConfigsRule::class),
        ];

        $this->filtrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with($configRules, $values)
            ->willReturn($expectedConfigRules);

        self::assertEquals(
            $expectedConfigRules,
            $this->basicMethodsConfigsRulesFiltrationService->getFilteredPaymentMethodsConfigsRules(
                $configRules,
                $context
            )
        );
    }
}
