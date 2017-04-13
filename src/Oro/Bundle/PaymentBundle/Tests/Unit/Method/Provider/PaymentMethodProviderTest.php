<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
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

    /**
     * @var CheckoutPaymentContextFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextFactory;

    /**
     * @var CheckoutRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutRepository;

    /**
     * @var PaymentMethodProvider
     */
    private $provider;

    public function setUp()
    {
        $this->paymentMethodProvidersRegistryMock = $this->createMock(PaymentMethodProvidersRegistryInterface::class);
        $this->paymentMethodsConfigsRulesProviderMock = $this
            ->createMock(PaymentMethodsConfigsRulesProviderInterface::class);
        $this->paymentContextMock = $this->createMock(PaymentContextInterface::class);
        $this->contextFactory = $this->createMock(CheckoutPaymentContextFactory::class);
        $this->checkoutRepository = $this->createMock(CheckoutRepository::class);

        $this->provider = new PaymentMethodProvider(
            $this->paymentMethodProvidersRegistryMock,
            $this->paymentMethodsConfigsRulesProviderMock,
            $this->contextFactory,
            $this->checkoutRepository
        );
    }

    public function testGetApplicablePaymentMethods()
    {
        $expectedPaymentMethodsMocks = $this->prepareMocks();
        $paymentMethods = $this->provider->getApplicablePaymentMethods($this->paymentContextMock);
        $this->assertEquals($expectedPaymentMethodsMocks, $paymentMethods);
    }


    public function testGetApplicablePaymentMethodsForTransaction()
    {
        $expectedPaymentMethodsMocks = $this->prepareMocks();

        $checkout = new Checkout();
        $checkoutId = 123;

        $transaction = new PaymentTransaction();
        $transaction->setTransactionOptions(['checkoutId' => $checkoutId]);

        $this->checkoutRepository->expects($this->once())->method('find')->with($checkoutId)->willReturn($checkout);

        $this->contextFactory->expects($this->once())
            ->method('create')
            ->with($checkout)
            ->willReturn($this->paymentContextMock);

        $paymentMethods = $this->provider->getApplicablePaymentMethodsForTransaction($transaction);

        $this->assertSame($expectedPaymentMethodsMocks, $paymentMethods);
    }

    public function testGetApplicablePaymentMethodsForTransactionWithoutCheckoutId()
    {
        $transaction = new PaymentTransaction();
        $transaction->setTransactionOptions(['checkoutId' => null]);

        $this->checkoutRepository->expects($this->never())->method('find');

        $this->assertNull($this->provider->getApplicablePaymentMethodsForTransaction($transaction));
    }

    public function testGetApplicablePaymentMethodsForTransactionWithoutCheckout()
    {
        $checkoutId = 123;
        $transaction = new PaymentTransaction();
        $transaction->setTransactionOptions(['checkoutId' => $checkoutId]);

        $this->checkoutRepository->expects($this->once())->method('find')->with($checkoutId)->willReturn(null);
        $this->contextFactory->expects($this->never())->method('create');

        $this->assertNull($this->provider->getApplicablePaymentMethodsForTransaction($transaction));
    }

    public function testGetApplicablePaymentMethodsForTransactionWithoutContext()
    {
        $checkout = new Checkout();
        $checkoutId = 123;

        $transaction = new PaymentTransaction();
        $transaction->setTransactionOptions(['checkoutId' => $checkoutId]);

        $this->checkoutRepository->expects($this->once())->method('find')->with($checkoutId)->willReturn($checkout);
        $this->contextFactory->expects($this->once())->method('create')->with($checkout)->willReturn(null);

        $this->assertNull($this->provider->getApplicablePaymentMethodsForTransaction($transaction));
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

    /**
     * @return PaymentMethodInterface[]|\PHPUnit_Framework_MockObject_MockObject[]
     */
    private function prepareMocks()
    {
        $configsRules = [];
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

        return [
            'SomeType' => $someTypeMethodMock,
            'PayPal' => $payPalMethodMock,
            'SomeOtherType' => $someOtherTypeMethodMock,
        ];
    }
}
