<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\CacheBundle\Tests\Unit\Provider\MemoryCacheProviderAwareTestTrait;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;

class ApplicablePaymentMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    use MemoryCacheProviderAwareTestTrait;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodProvider;

    /** @var MethodsConfigsRulesByContextProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodsConfigsRulesProvider;

    /** @var PaymentContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentContext;

    /** @var ApplicablePaymentMethodsProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentMethodsConfigsRulesProvider = $this->createMock(
            MethodsConfigsRulesByContextProviderInterface::class
        );
        $this->paymentContext = $this->createMock(PaymentContextInterface::class);

        $this->provider = new ApplicablePaymentMethodsProvider(
            $this->paymentMethodProvider,
            $this->paymentMethodsConfigsRulesProvider
        );
    }

    public function testGetApplicablePaymentMethodsWhenCache(): void
    {
        $paymentMethods = ['sample_method1'];
        $this->mockMemoryCacheProvider($paymentMethods);
        $this->setMemoryCacheProvider($this->provider);

        $this->assertEquals(
            $paymentMethods,
            $this->provider->getApplicablePaymentMethods($this->paymentContext)
        );
    }

    public function testGetApplicablePaymentMethods()
    {
        $configsRules[] = $this->getPaymentMethodsConfigsRule(['SomeType']);
        $configsRules[] = $this->getPaymentMethodsConfigsRule(['PayPal', 'SomeOtherType']);

        $this->paymentMethodsConfigsRulesProvider->expects($this->once())
            ->method('getPaymentMethodsConfigsRules')
            ->with($this->paymentContext)
            ->willReturn($configsRules);

        $someTypeMethod = $this->getPaymentMethod('SomeType');
        $payPalMethod = $this->getPaymentMethod('PayPal');
        $someOtherTypeMethod = $this->getPaymentMethod('SomeOtherType');

        $this->paymentMethodProvider->expects($this->any())
            ->method('hasPaymentMethod')
            ->willReturnMap([
                ['SomeType', true],
                ['PayPal', true],
                ['SomeOtherType', true],
            ]);

        $this->paymentMethodProvider->expects($this->any())
            ->method('getPaymentMethod')
            ->willReturnMap([
                ['SomeType', $someTypeMethod],
                ['PayPal', $payPalMethod],
                ['SomeOtherType', $someOtherTypeMethod],
            ]);

        $expectedPaymentMethods = [
            'SomeType' => $someTypeMethod,
            'PayPal' => $payPalMethod,
            'SomeOtherType' => $someOtherTypeMethod,
        ];

        $paymentMethods = $this->provider->getApplicablePaymentMethods($this->paymentContext);

        $this->assertEquals($expectedPaymentMethods, $paymentMethods);
    }

    public function testGetApplicablePaymentMethodsWhenMemoryCacheProvider(): void
    {
        $this->mockMemoryCacheProvider();
        $this->setMemoryCacheProvider($this->provider);

        $this->testGetApplicablePaymentMethods();
    }

    private function getPaymentMethodsConfigsRule(array $configuredMethodTypes): PaymentMethodsConfigsRule
    {
        $methodConfigs = [];
        foreach ($configuredMethodTypes as $configuredMethodType) {
            $methodConfigs[] = $this->getPaymentMethodConfig($configuredMethodType);
        }

        $configsRule = $this->createMock(PaymentMethodsConfigsRule::class);
        $configsRule->expects($this->once())
            ->method('getMethodConfigs')
            ->willReturn($methodConfigs);

        return $configsRule;
    }

    private function getPaymentMethodConfig(string $configuredMethodType): PaymentMethodConfig
    {
        $methodConfig = $this->createMock(PaymentMethodConfig::class);
        $methodConfig->expects($this->exactly(2))
            ->method('getType')
            ->willReturn($configuredMethodType);

        return $methodConfig;
    }

    private function getPaymentMethod(string $methodType): PaymentMethodInterface
    {
        $method = $this->createMock(PaymentMethodInterface::class);
        $method->expects($this->never())
            ->method('getIdentifier')
            ->willReturn($methodType);
        $method->expects($this->once())
            ->method('isApplicable')
            ->willReturn(true);

        return $method;
    }
}
