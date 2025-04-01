<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\RuleFiltration;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\RuleFiltration\EnabledPaymentRuleFiltrationService;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnabledPaymentRuleFiltrationServiceTest extends TestCase
{
    private RuleFiltrationServiceInterface&MockObject $baseFiltrationService;

    private EnabledPaymentRuleFiltrationService $enabledPaymentRuleFiltrationService;

    protected function setUp(): void
    {
        $this->baseFiltrationService = $this->createMock(RuleFiltrationServiceInterface::class);
        $paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $paymentMethodProvider->expects($this->any())
            ->method('hasPaymentMethod')
            ->willReturnMap([
                ['valid_type', true],
                ['invalid_type', false],
            ]);
        $paymentMethodProvider->expects($this->any())
            ->method('getPaymentMethod')
            ->with('valid_type')
            ->willReturn(self::createMock(PaymentMethodInterface::class));

        $this->enabledPaymentRuleFiltrationService = new EnabledPaymentRuleFiltrationService(
            $paymentMethodProvider,
            $this->baseFiltrationService
        );
    }

    public function testGetFilteredRuleOwners(): void
    {
        $paymentMethodConfigs = $this->createPaymentMethodConfig('valid_type');
        $paymentMethodsConfigsRule = $this->createPaymentMethodsConfigsRule([$paymentMethodConfigs]);

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([$paymentMethodsConfigsRule], [])
            ->willReturn([]);

        $this->enabledPaymentRuleFiltrationService->getFilteredRuleOwners([$paymentMethodsConfigsRule], []);
    }

    public function testGetFilteredInvalidRuleOwners(): void
    {
        $paymentMethodConfigs = $this->createPaymentMethodConfig('invalid_type');
        $paymentMethodsConfigsRule = $this->createPaymentMethodsConfigsRule([$paymentMethodConfigs]);

        $this->baseFiltrationService->expects(self::once())
            ->method('getFilteredRuleOwners')
            ->with([], [])
            ->willReturn([]);

        $this->enabledPaymentRuleFiltrationService->getFilteredRuleOwners([$paymentMethodsConfigsRule], []);
    }

    private function createPaymentMethodsConfigsRule(array $methodConfigs): PaymentMethodsConfigsRule
    {
        $paymentMethodConfigRule = new PaymentMethodsConfigsRule();
        foreach ($methodConfigs as $methodConfig) {
            $paymentMethodConfigRule->addMethodConfig($methodConfig);
        }

        return $paymentMethodConfigRule;
    }

    private function createPaymentMethodConfig(string $type): PaymentMethodConfig
    {
        $paymentMethodConfig = new PaymentMethodConfig();
        $paymentMethodConfig->setType($type);

        return $paymentMethodConfig;
    }
}
