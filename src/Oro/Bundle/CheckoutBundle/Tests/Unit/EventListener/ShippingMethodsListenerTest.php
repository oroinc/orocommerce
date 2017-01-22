<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\ShippingMethodsListener;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutShippingContextFactory;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Provider\ShippingMethodsConfigsRulesProviderInterface;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingMethodsListenerTest extends AbstractMethodsListenerTest
{
    use EntityTrait;

    /** @var OrderAddressSecurityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $orderAddressSecurityProvider;

    /** @var OrderAddressProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $addressProvider;

    /**
     * @var ShippingMethodsConfigsRulesProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configsRuleProvider;

    /**
     * @var CheckoutShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject
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

        $this->configsRuleProvider = $this->getMockBuilder(ShippingMethodsConfigsRulesProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextFactory = $this->getMockBuilder(CheckoutShippingContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getListener(OrderAddressManager $orderAddressManager)
    {
        return new ShippingMethodsListener(
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
            'shipping manual edit granted and no configs returned' => [
                'shippingManualEdit' => true,
                'billingManualEdit' => false,
                'methodConfigs' => []
            ],
            'billing manual edit granted and method configs returned' => [
                'shippingManualEdit' => false,
                'billingManualEdit' => true,
                'methodConfigs' => [$this->getMethodConfig(['id' => 1]), $this->getMethodConfig(['id' => 2])]
            ],
            'shipping and billing manual edit granted and method config returned' => [
                'shippingManualEdit' => true,
                'billingManualEdit' => true,
                'methodConfigs' => [$this->getMethodConfig(['id' => 1])]
            ],
        ];
    }

    /**
     * @dataProvider manualEditGrantedDataProvider
     * @param bool $shippingManualEdit
     * @param bool $billingManualEdit
     * @param array $methodConfigs
     */
    public function testOnStartCheckoutWhenIsApplicableAndManualEditGranted(
        $shippingManualEdit,
        $billingManualEdit,
        array $methodConfigs
    ) {
        $context = new ActionData(['checkout' => $this->getEntity(Checkout::class), 'validateOnStartCheckout' => true]);
        $event = new ExtendableConditionEvent($context);

        $this->orderAddressSecurityProvider
            ->expects($this->atLeast(1))
            ->method('isManualEditGranted')
            ->willReturnMap([
                [AddressType::TYPE_SHIPPING, $shippingManualEdit],
                [AddressType::TYPE_BILLING, $billingManualEdit]
            ]);

        $shippingContext = $this->createMock(ShippingContextInterface::class);
        $this->contextFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->isInstanceOf(Checkout::class))
            ->willReturn($shippingContext);

        $this->configsRuleProvider
            ->expects($this->once())
            ->method('getFilteredShippingMethodsConfigsRegardlessDestination')
            ->with($shippingContext)
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
        $shippingContext = $this->createMock(ShippingContextInterface::class);

        $this->contextFactory
            ->expects($this->exactly($expectedCalls))
            ->method('create')
            ->with($this->callback(function (Checkout $checkout) {
                $this->assertInstanceOf(OrderAddress::class, $checkout->getShippingAddress());

                return $checkout instanceof Checkout;
            }))
            ->willReturn($shippingContext);

        $this->configsRuleProvider
            ->expects($this->exactly($expectedCalls))
            ->method('getFilteredShippingMethodsConfigsRegardlessDestination')
            ->with($shippingContext)
            ->willReturnOnConsecutiveCalls(...$willReturnConfigsOnConsecutiveCalls);
    }

    /**
     * {@inheritdoc}
     */
    private function getMethodConfig(array $params)
    {
        return $this->getEntity(ShippingMethodsConfigsRule::class, $params);
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

        $shippingCustomerAddress = $this->getEntity(OrderAddress::class, ['id' => 1]);
        $shippingCustomerUserAddress = $this->getEntity(OrderAddress::class, ['id' => 3]);
        $billingCustomerAddress = $this->getEntity(OrderAddress::class, ['id' => 2]);
        $billingCustomerUserAddress = $this->getEntity(OrderAddress::class, ['id' => 4]);

        return [
            'no configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'hasErrors' => true,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_SHIPPING, [$shippingCustomerAddress]],
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]]
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_SHIPPING, [$shippingCustomerUserAddress]],
                    [$customerUser, AddressType::TYPE_BILLING, [$billingCustomerUserAddress]]
                ],
                'consecutiveAddresses' => [
                    [$shippingCustomerAddress],
                    [$shippingCustomerUserAddress],
                    [$billingCustomerAddress],
                    [$billingCustomerUserAddress]
                ],
                'expectedCalls' => 4,
                'onConsecutiveMethodConfigs' => [[], [], [], []]
            ],
            'has configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'hasErrors' => false,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_SHIPPING, []],
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]]
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_SHIPPING, [$shippingCustomerUserAddress]],
                    [$customerUser, AddressType::TYPE_BILLING, []]
                ],
                'consecutiveAddresses' => [[$shippingCustomerUserAddress], [$billingCustomerAddress]],
                'expectedCalls' => 2,
                'onConsecutiveMethodConfigs' => [
                    [],
                    [$this->getMethodConfig(['id' => 1])]
                ]
            ],
        ];
    }

    /**
     * @dataProvider notManualEditDataProvider
     *
     * @param Checkout $checkout
     * @param bool $hasErrors
     * @param array $customerAddressesMap
     * @param array $customerUserAddressesMap
     * @param array $consecutiveAddresses
     * @param int $expectedCalls
     * @param array $onConsecutiveMethodConfigs
     */
    public function testOnStartCheckoutWhenIsManualEditNotGranted(
        $checkout,
        $hasErrors,
        array $customerAddressesMap,
        array $customerUserAddressesMap,
        array $consecutiveAddresses,
        $expectedCalls,
        array $onConsecutiveMethodConfigs
    ) {
        $context = new ActionData(['checkout' => $checkout, 'validateOnStartCheckout' => true]);

        $event = new ExtendableConditionEvent($context);

        $this->orderAddressSecurityProvider
            ->expects($this->exactly(2))
            ->method('isManualEditGranted')
            ->willReturnMap([
                [AddressType::TYPE_SHIPPING, false],
                [AddressType::TYPE_BILLING, false]
            ]);

        $this->addressProvider
            ->expects($this->exactly(2))
            ->method('getCustomerAddresses')
            ->willReturnMap($customerAddressesMap);

        $this->addressProvider
            ->expects($this->exactly(2))
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

        $this->assertEquals($hasErrors, !$event->getErrors()->isEmpty());
    }
}
