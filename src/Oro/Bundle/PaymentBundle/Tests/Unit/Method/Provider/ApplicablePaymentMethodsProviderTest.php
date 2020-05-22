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

    /**
     * @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentMethodProviderMock;

    /**
     * @var MethodsConfigsRulesByContextProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentMethodsConfigsRulesProviderMock;

    /**
     * @var PaymentContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $paymentContextMock;

    /**
     * @var ApplicablePaymentMethodsProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->paymentMethodProviderMock = $this->createMock(PaymentMethodProviderInterface::class);

        $this->paymentMethodsConfigsRulesProviderMock = $this
            ->createMock(MethodsConfigsRulesByContextProviderInterface::class);

        $this->paymentContextMock = $this->createMock(PaymentContextInterface::class);

        $this->provider = new ApplicablePaymentMethodsProvider(
            $this->paymentMethodProviderMock,
            $this->paymentMethodsConfigsRulesProviderMock
        );
    }

    public function testGetApplicablePaymentMethodsWhenCache(): void
    {
        $paymentMethods = ['sample_method1'];
        $this->mockMemoryCacheProvider($paymentMethods);
        $this->setMemoryCacheProvider($this->provider);

        $this->assertEquals(
            $paymentMethods,
            $this->provider->getApplicablePaymentMethods($this->paymentContextMock)
        );
    }

    public function testGetApplicablePaymentMethods()
    {
        $configsRules[] = $this->createPaymentMethodsConfigsRuleMock(['SomeType']);
        $configsRules[] = $this->createPaymentMethodsConfigsRuleMock(['PayPal', 'SomeOtherType']);

        $this->paymentMethodsConfigsRulesProviderMock
            ->expects($this->once())
            ->method('getPaymentMethodsConfigsRules')
            ->with($this->paymentContextMock)
            ->willReturn($configsRules);

        $someTypeMethodMock = $this->createPaymentMethodMock('SomeType');
        $payPalMethodMock = $this->createPaymentMethodMock('PayPal');
        $someOtherTypeMethodMock = $this->createPaymentMethodMock('SomeOtherType');

        $this->paymentMethodProviderMock
            ->expects($this->any())
            ->method('hasPaymentMethod')
            ->willReturnMap([
                ['SomeType', true],
                ['PayPal', true],
                ['SomeOtherType', true],
            ]);

        $this->paymentMethodProviderMock
            ->expects($this->any())
            ->method('getPaymentMethod')
            ->willReturnMap([
                ['SomeType', $someTypeMethodMock],
                ['PayPal', $payPalMethodMock],
                ['SomeOtherType', $someOtherTypeMethodMock],
            ]);

        $expectedPaymentMethodsMocks = [
            'SomeType' => $someTypeMethodMock,
            'PayPal' => $payPalMethodMock,
            'SomeOtherType' => $someOtherTypeMethodMock,
        ];

        $paymentMethods = $this->provider->getApplicablePaymentMethods($this->paymentContextMock);

        $this->assertEquals($expectedPaymentMethodsMocks, $paymentMethods);
    }

    public function testGetApplicablePaymentMethodsWhenMemoryCacheProvider(): void
    {
        $this->mockMemoryCacheProvider();
        $this->setMemoryCacheProvider($this->provider);

        $this->testGetApplicablePaymentMethods();
    }

    /**
     * @var string[] $configuredMethodTypes
     *
     * @return PaymentMethodsConfigsRule|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createPaymentMethodsConfigsRuleMock(array $configuredMethodTypes)
    {
        $methodConfigMocks = [];
        foreach ($configuredMethodTypes as $configuredMethodType) {
            $methodConfigMocks[] = $this->createPaymentMethodConfigMock($configuredMethodType);
        }

        $configsRuleMock = $this->getMockBuilder(PaymentMethodsConfigsRule::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configsRuleMock
            ->expects($this->once())
            ->method('getMethodConfigs')
            ->willReturn($methodConfigMocks);

        return $configsRuleMock;
    }

    /**
     * @param string $configuredMethodType
     *
     * @return PaymentMethodConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createPaymentMethodConfigMock($configuredMethodType)
    {
        $methodConfigMock = $this->getMockBuilder(PaymentMethodConfig::class)
            ->disableOriginalConstructor()
            ->getMock();

        $methodConfigMock
            ->expects($this->exactly(2))
            ->method('getType')
            ->willReturn($configuredMethodType);

        return $methodConfigMock;
    }

    /**
     * @param string $methodType
     *
     * @return PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createPaymentMethodMock($methodType)
    {
        /** @var PaymentMethodInterface|\PHPUnit\Framework\MockObject\MockObject $method */
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
