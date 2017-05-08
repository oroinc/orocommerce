<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentMethodsConfigsRulesProviderInterface;

class PaymentMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentMethodProvidersRegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethodProvidersRegistryMock;

    /**
     * @var PaymentMethodsConfigsRulesProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethodsConfigsRulesProviderMock;

    /**
     * @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentContextMock;

    public function setUp()
    {
        $this->paymentMethodProvidersRegistryMock = $this->createMock(PaymentMethodProvidersRegistryInterface::class);

        $this->paymentMethodsConfigsRulesProviderMock = $this
            ->getMockBuilder(PaymentMethodsConfigsRulesProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentContextMock = $this->getMockBuilder(PaymentContextInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetApplicablePaymentMethods()
    {
        $configsRules[] = $this->createPaymentMethodsConfigsRuleMock(['SomeType']);
        $configsRules[] = $this->createPaymentMethodsConfigsRuleMock(['PayPal', 'SomeOtherType']);

        $this->paymentMethodsConfigsRulesProviderMock
            ->expects($this->once())
            ->method('getFilteredPaymentMethodsConfigs')
            ->with($this->paymentContextMock)
            ->willReturn($configsRules);

        $someTypeMethodMock = $this->createPaymentMethodMock('SomeType');
        $payPalMethodMock = $this->createPaymentMethodMock('PayPal');
        $someOtherTypeMethodMock = $this->createPaymentMethodMock('SomeOtherType');

        $paymentMethodProvider = $this->getMockBuilder(PaymentMethodProviderInterface::class)->getMock();

        $paymentMethodProvider
            ->expects($this->exactly(3))
            ->method('getPaymentMethod')
            ->will(
                $this->returnValueMap(
                    [
                        ['SomeType', $someTypeMethodMock],
                        ['PayPal', $payPalMethodMock],
                        ['SomeOtherType', $someOtherTypeMethodMock],
                    ]
                )
            );

        $this->paymentMethodProvidersRegistryMock
            ->expects($this->any())
            ->method('getPaymentMethodProviders')
            ->willReturn([$paymentMethodProvider]);

        $expectedPaymentMethodsMocks = [
            'SomeType' => $someTypeMethodMock,
            'PayPal' => $payPalMethodMock,
            'SomeOtherType' => $someOtherTypeMethodMock,
        ];

        $provider = new PaymentMethodProvider(
            $this->paymentMethodProvidersRegistryMock,
            $this->paymentMethodsConfigsRulesProviderMock
        );

        $paymentMethods = $provider->getApplicablePaymentMethods($this->paymentContextMock);

        $this->assertEquals($expectedPaymentMethodsMocks, $paymentMethods);
    }

    /**
     * @var string[] $configuredMethodTypes
     *
     * @return PaymentMethodsConfigsRule|\PHPUnit_Framework_MockObject_MockObject
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
     * @return PaymentMethodConfig|\PHPUnit_Framework_MockObject_MockObject
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
     * @return PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPaymentMethodMock($methodType)
    {
        /** @var PaymentMethodInterface|\PHPUnit_Framework_MockObject_MockObject $method */
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
