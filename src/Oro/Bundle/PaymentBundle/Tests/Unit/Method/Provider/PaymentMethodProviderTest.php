<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentMethodsConfigsRulesProviderInterface;

class PaymentMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentMethodRegistryMock;

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
        $this->paymentMethodRegistryMock = $this->getMockBuilder(PaymentMethodRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->paymentMethodRegistryMock
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

        $expectedPaymentMethodsMocks = [
            'SomeType' => $someTypeMethodMock,
            'PayPal' => $payPalMethodMock,
            'SomeOtherType' => $someOtherTypeMethodMock,
        ];

        $provider = new PaymentMethodProvider(
            $this->paymentMethodRegistryMock,
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
     * @return PaymentMethodInterface|\PHPUnit_Framework_MockObject_Builder_InvocationMocker
     */
    private function createPaymentMethodMock($methodType)
    {
        $method = $this->createMock(PaymentMethodInterface::class);
        $method->expects($this->never())
            ->method('getType')
            ->willReturn($methodType);

        $method->expects($this->once())
            ->method('isApplicable')
            ->willReturn(true);

        return $method;
    }
}
