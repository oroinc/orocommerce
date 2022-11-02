<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\RuleFiltration\Basic;

use Oro\Bundle\PaymentBundle\Context\Converter\PaymentContextToRulesValueConverterInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\RuleFiltration\Basic\BasicMethodsConfigsRulesFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class BasicMethodsConfigsRulesFiltrationServiceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RuleFiltrationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filtrationService;

    /**
     * @var PaymentContextToRulesValueConverterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentContextToRuleValuesConverter;

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
        $this->paymentContextToRuleValuesConverter = $this
            ->createMock(PaymentContextToRulesValueConverterInterface::class);

        $this->basicMethodsConfigsRulesFiltrationService = new BasicMethodsConfigsRulesFiltrationService(
            $this->filtrationService,
            $this->paymentContextToRuleValuesConverter
        );
    }

    /**
     * {@inheritDoc}
     */
    public function testGetFilteredPaymentMethodsConfigsRules()
    {
        $configRules = [
            $this->createPaymentMethodsConfigsRule(),
            $this->createPaymentMethodsConfigsRule(),
        ];
        $context = $this->createContextMock();
        $values = [
            'currency' => 'USD',
        ];

        $this->paymentContextToRuleValuesConverter->expects(static::once())
            ->method('convert')
            ->with($context)
            ->willReturn($values);

        $expectedConfigRules = [
            $this->createPaymentMethodsConfigsRule(),
            $this->createPaymentMethodsConfigsRule(),
        ];

        $this->filtrationService->expects(static::once())
            ->method('getFilteredRuleOwners')
            ->with($configRules, $values)
            ->willReturn($expectedConfigRules);

        static::assertEquals(
            $expectedConfigRules,
            $this->basicMethodsConfigsRulesFiltrationService->getFilteredPaymentMethodsConfigsRules(
                $configRules,
                $context
            )
        );
    }

    /**
     * @return PaymentContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createContextMock()
    {
        return $this->createMock(PaymentContextInterface::class);
    }

    /**
     * @return PaymentMethodsConfigsRule|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createPaymentMethodsConfigsRule()
    {
        return $this->createMock(PaymentMethodsConfigsRule::class);
    }
}
