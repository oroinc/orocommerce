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
        $typesPerForConfig = [
            ['SomeType'],
            ['PayPal', 'SomeOtherType'],
        ];

        $configsRules = $this->getPaymentMethodsConfigsRulesMock($typesPerForConfig);

        $this->paymentMethodsConfigsRulesProviderMock
            ->expects($this->once())
            ->method('getFilteredPaymentMethodsConfigs')
            ->with($this->paymentContextMock)
            ->willReturn($configsRules);

        $expectedPaymentMethodsMocks = $this->buildRegistryMock($typesPerForConfig);

        $provider = new PaymentMethodProvider(
            $this->paymentMethodRegistryMock,
            $this->paymentMethodsConfigsRulesProviderMock
        );

        $paymentMethods = $provider->getApplicablePaymentMethods($this->paymentContextMock);

        $this->assertEquals($expectedPaymentMethodsMocks, $paymentMethods);
    }

    /**
     * @var array|string[] $typesPerForConfig
     *
     * @return PaymentMethodsConfigsRule[]|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentMethodsConfigsRulesMock(array $typesPerForConfig)
    {
        $configsRulesMocks = [];

        foreach ($typesPerForConfig as $configuredMethodTypes) {
            $methodConfigMocks = [];

            foreach ($configuredMethodTypes as $configuredMethodType) {
                $methodConfigMock = $this->getMockBuilder(PaymentMethodConfig::class)
                    ->disableOriginalConstructor()
                    ->getMock();
                $methodConfigMock
                    ->expects($this->exactly(2))
                    ->method('getType')
                    ->willReturn($configuredMethodType);
                $methodConfigMocks[] = $methodConfigMock;
            }

            $configsRuleMock = $this->getMockBuilder(PaymentMethodsConfigsRule::class)
                ->disableOriginalConstructor()
                ->getMock();

            $configsRuleMock
                ->expects($this->once())
                ->method('getMethodConfigs')
                ->willReturn($methodConfigMocks);

            $configsRulesMocks[] = $configsRuleMock;
        }

        return $configsRulesMocks;
    }

    /**
     * @param array|string[] $typesPerForConfig
     *
     * @return array|PaymentMethodInterface[]|\PHPUnit_Framework_MockObject_MockObject
     */
    private function buildRegistryMock(array $typesPerForConfig)
    {
        $paymentMethodsMocks = [];

        $counter = 0;
        foreach ($typesPerForConfig as $configuredMethodTypes) {
            foreach ($configuredMethodTypes as $configuredMethodType) {
                $paymentMethodMock = $this->getMock(PaymentMethodInterface::class);

                // so that one mock is different from another
                $paymentMethodMock
                    ->expects($this->never())
                    ->method('getType')
                    ->willReturn($configuredMethodType);

                $this->paymentMethodRegistryMock
                    ->expects($this->at($counter))
                    ->method('getPaymentMethod')
                    ->with($configuredMethodType)
                    ->willReturn($paymentMethodMock);

                $counter++;
                $paymentMethodsMocks[$configuredMethodType] = $paymentMethodMock;
            }
        }

        return $paymentMethodsMocks;
    }
}
