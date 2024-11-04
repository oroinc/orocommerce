<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\ApplicablePaymentMethodsProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;

class ApplicablePaymentMethodsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodProvider;

    /** @var MethodsConfigsRulesByContextProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodsConfigsRulesProvider;

    /** @var MemoryCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $memoryCacheProvider;

    /** @var ApplicablePaymentMethodsProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);
        $this->paymentMethodsConfigsRulesProvider = $this->createMock(
            MethodsConfigsRulesByContextProviderInterface::class
        );
        $this->memoryCacheProvider = $this->createMock(MemoryCacheProviderInterface::class);

        $this->provider = new ApplicablePaymentMethodsProvider(
            $this->paymentMethodProvider,
            $this->paymentMethodsConfigsRulesProvider,
            $this->memoryCacheProvider
        );
    }

    private function getPaymentMethod(): PaymentMethodInterface
    {
        $method = $this->createMock(PaymentMethodInterface::class);
        $method->expects(self::any())
            ->method('isApplicable')
            ->willReturn(true);

        return $method;
    }

    private function getPaymentMethodConfig(string $configuredMethodType): PaymentMethodConfig
    {
        $methodConfig = $this->createMock(PaymentMethodConfig::class);
        $methodConfig->expects(self::any())
            ->method('getType')
            ->willReturn($configuredMethodType);

        return $methodConfig;
    }

    private function getPaymentMethodsConfigsRule(array $configuredMethodTypes): PaymentMethodsConfigsRule
    {
        $methodConfigs = [];
        foreach ($configuredMethodTypes as $configuredMethodType) {
            $methodConfigs[] = $this->getPaymentMethodConfig($configuredMethodType);
        }

        $configsRule = $this->createMock(PaymentMethodsConfigsRule::class);
        $configsRule->expects(self::any())
            ->method('getMethodConfigs')
            ->willReturn($methodConfigs);

        return $configsRule;
    }

    public function testGetApplicablePaymentMethods(): void
    {
        $paymentContext = $this->createMock(PaymentContextInterface::class);

        $configsRules = [
            $this->getPaymentMethodsConfigsRule(['SomeType']),
            $this->getPaymentMethodsConfigsRule(['PayPal', 'SomeOtherType'])
        ];

        $someTypeMethod = $this->getPaymentMethod();
        $payPalMethod = $this->getPaymentMethod();
        $someOtherTypeMethod = $this->getPaymentMethod();

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->with(self::identicalTo(['payment_context' => $paymentContext]))
            ->willReturnCallback(function ($arguments, $callable) {
                return $callable($arguments);
            });

        $this->paymentMethodsConfigsRulesProvider->expects(self::once())
            ->method('getPaymentMethodsConfigsRules')
            ->with($paymentContext)
            ->willReturn($configsRules);

        $this->paymentMethodProvider->expects(self::exactly(3))
            ->method('hasPaymentMethod')
            ->willReturnMap([
                ['SomeType', true],
                ['PayPal', true],
                ['SomeOtherType', true],
            ]);

        $this->paymentMethodProvider->expects(self::exactly(3))
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

        self::assertEquals(
            $expectedPaymentMethods,
            $this->provider->getApplicablePaymentMethods($paymentContext)
        );
    }

    public function testGetApplicablePaymentMethodsWhenDataCached(): void
    {
        $paymentContext = $this->createMock(PaymentContextInterface::class);

        $cachedPaymentMethods = [
            'SomeType' => $this->getPaymentMethod()
        ];

        $this->memoryCacheProvider->expects(self::once())
            ->method('get')
            ->with(self::identicalTo(['payment_context' => $paymentContext]))
            ->willReturnCallback(function () use ($cachedPaymentMethods) {
                return $cachedPaymentMethods;
            });

        $this->paymentMethodsConfigsRulesProvider->expects(self::never())
            ->method('getPaymentMethodsConfigsRules');

        $this->paymentMethodProvider->expects(self::never())
            ->method('hasPaymentMethod');

        $this->paymentMethodProvider->expects(self::never())
            ->method('getPaymentMethod');

        self::assertEquals(
            $cachedPaymentMethods,
            $this->provider->getApplicablePaymentMethods($paymentContext)
        );
    }
}
