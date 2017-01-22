<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\PaymentMethodsListener;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Provider\PaymentMethodsConfigsRulesProviderInterface;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class PaymentMethodsListenerTest extends AbstractMethodsListenerTest
{
    use EntityTrait;

    /** @var OrderAddressSecurityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $orderAddressSecurityProvider;

    /** @var OrderAddressProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $addressProvider;

    /**
     * @var PaymentMethodsConfigsRulesProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configsRuleProvider;

    /**
     * @var CheckoutPaymentContextFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextFactory;

    protected function setUp()
    {
        $this->orderAddressSecurityProvider = $this->getMockBuilder(OrderAddressSecurityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressProvider = $this->getMockBuilder(OrderAddressProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configsRuleProvider = $this->getMockBuilder(PaymentMethodsConfigsRulesProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextFactory = $this->getMockBuilder(CheckoutPaymentContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener(OrderAddressManager $orderAddressManager)
    {
        return new PaymentMethodsListener(
            $this->addressProvider,
            $this->orderAddressSecurityProvider,
            $orderAddressManager,
            $this->configsRuleProvider,
            $this->contextFactory
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function expectsNoInvocationOfManualEditGranted()
    {
        $this->orderAddressSecurityProvider
            ->expects($this->never())
            ->method('isManualEditGranted');
    }

    /**
     * @return array
     */
    public function manualEditGrantedDataProvider()
    {
        return [
            'manual edit granted and no configs returned' => [
                'billingManualEdit' => true,
                'methodConfigs' => []
            ],
            'manual edit granted and method configs returned' => [
                'billingManualEdit' => true,
                'methodConfigs' => [$this->getMethodConfig(['id' => 1]), $this->getMethodConfig(['id' => 2])]
            ],
        ];
    }

    /**
     * @dataProvider manualEditGrantedDataProvider
     * @param bool $billingManualEdit
     * @param array $methodConfigs
     */
    public function testOnStartCheckoutWhenIsApplicableAndManualEditGranted(
        $billingManualEdit,
        array $methodConfigs
    ) {
        $context = new ActionData(['checkout' => $this->getEntity(Checkout::class), 'validateOnStartCheckout' => true]);
        $event = new ExtendableConditionEvent($context);

        $this->orderAddressSecurityProvider
            ->expects($this->once())
            ->method('isManualEditGranted')
            ->willReturnMap([
                [AddressType::TYPE_BILLING, $billingManualEdit]
            ]);

        $paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->contextFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(Checkout::class))
            ->willReturn($paymentContext);

        $this->configsRuleProvider
            ->expects($this->once())
            ->method('getFilteredPaymentMethodsConfigsRegardlessDestination')
            ->with($paymentContext)
            ->willReturn($methodConfigs);

        $this->listener->onStartCheckout($event);

        $this->assertEquals(!empty($methodConfigs), $event->getErrors()->isEmpty());
    }

    /**
     * {@inheritdoc}
     */
    protected function expectsHasMethodsConfigsForAddressesInvocation(
        $expectedCalls,
        array $willReturnConfigsOnConsecutiveCalls
    ) {
        $paymentContext = $this->createMock(PaymentContextInterface::class);

        $this->contextFactory
            ->expects($this->exactly($expectedCalls))
            ->method('create')
            ->with($this->callback(function (Checkout $checkout) {
                $this->assertInstanceOf(OrderAddress::class, $checkout->getBillingAddress());

                return $checkout instanceof Checkout;
            }))
            ->willReturn($paymentContext);

        $this->configsRuleProvider
            ->expects($this->exactly($expectedCalls))
            ->method('getFilteredPaymentMethodsConfigsRegardlessDestination')
            ->with($paymentContext)
            ->willReturnOnConsecutiveCalls(...$willReturnConfigsOnConsecutiveCalls);
    }

    /**
     * {@inheritdoc}
     */
    protected function getMethodConfig(array $params)
    {
        return $this->getEntity(PaymentMethodsConfigsRule::class, $params);
    }

    /**
     * @return array
     */
    public function notManualEditDataProvider()
    {
        $customer = $this->getEntity(Customer::class);
        $customerUser = $this->getEntity(CustomerUser::class);
        $checkout = $this->getEntity(Checkout::class, [
            'customer' => $customer,
            'customerUser' => $customerUser,
        ]);

        $billingCustomerAddress = $this->getEntity(OrderAddress::class, ['id' => 2]);
        $billingCustomerUserAddress = $this->getEntity(OrderAddress::class, ['id' => 4]);

        return [
            'error because no configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]]
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_BILLING, [$billingCustomerUserAddress]]
                ],
                'consecutiveAddresses' => [
                    [$billingCustomerAddress],
                    [$billingCustomerUserAddress]
                ],
                'expectedCalls' => 2,
                'onConsecutiveMethodConfigs' => [[], []]
            ],
            'no error because has configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]]
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_BILLING, []]
                ],
                'consecutiveAddresses' => [[$billingCustomerAddress]],
                'expectedCalls' => 1,
                'onConsecutiveMethodConfigs' => [
                    [$this->getMethodConfig(['id' => 1])]
                ]
            ],
        ];
    }

    /**
     * @dataProvider notManualEditDataProvider
     *
     * @param Checkout $checkout
     * @param array $customerAddressesMap
     * @param array $customerUserAddressesMap
     * @param array $consecutiveAddresses
     * @param int $expectedCalls
     * @param array $onConsecutiveMethodConfigs
     */
    public function testOnStartCheckoutWhenIsManualEditNotGranted(
        $checkout,
        array $customerAddressesMap,
        array $customerUserAddressesMap,
        array $consecutiveAddresses,
        $expectedCalls,
        array $onConsecutiveMethodConfigs
    ) {
        $context = new ActionData(['checkout' => $checkout, 'validateOnStartCheckout' => true]);

        $event = new ExtendableConditionEvent($context);

        $this->orderAddressSecurityProvider
            ->expects($this->once())
            ->method('isManualEditGranted')
            ->willReturnMap([
                [AddressType::TYPE_SHIPPING, false],
                [AddressType::TYPE_BILLING, false]
            ]);

        $this->addressProvider
            ->expects($this->once())
            ->method('getCustomerAddresses')
            ->willReturnMap($customerAddressesMap);

        $this->addressProvider
            ->expects($this->once())
            ->method('getCustomerUserAddresses')
            ->willReturnMap($customerUserAddressesMap);

        $orderAddress = $this->getEntity(OrderAddress::class, ['id' => 7]);

        $this->orderAddressManager
            ->expects($this->exactly($expectedCalls))
            ->method('updateFromAbstract')
            ->withConsecutive(...$consecutiveAddresses)
            ->willReturn($orderAddress);

        $this->expectsHasMethodsConfigsForAddressesInvocation($expectedCalls, $onConsecutiveMethodConfigs);

        $this->listener->onStartCheckout($event);

        $this->assertEquals(!empty(array_filter($onConsecutiveMethodConfigs)), $event->getErrors()->isEmpty());
    }
}
