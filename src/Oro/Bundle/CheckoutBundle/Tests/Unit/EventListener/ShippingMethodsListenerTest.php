<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\ShippingMethodsListener;
use Oro\Bundle\CheckoutBundle\Provider\AvailableShippingMethodCheckerInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\ReflectionUtil;

class ShippingMethodsListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderAddressSecurityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $orderAddressSecurityProvider;

    /** @var OrderAddressManager|\PHPUnit\Framework\MockObject\MockObject */
    private $orderAddressManager;

    /** @var OrderAddressProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $addressProvider;

    /** @var AvailableShippingMethodCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $availableShippingMethodChecker;

    /** @var ShippingMethodsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $this->orderAddressManager = $this->createMock(OrderAddressManager::class);
        $this->addressProvider = $this->createMock(OrderAddressProvider::class);
        $this->availableShippingMethodChecker = $this->createMock(AvailableShippingMethodCheckerInterface::class);

        $this->listener = new ShippingMethodsListener(
            $this->addressProvider,
            $this->orderAddressSecurityProvider,
            $this->orderAddressManager,
            $this->availableShippingMethodChecker
        );
    }

    private function getOrderAddress(int $id): OrderAddress
    {
        $address = new OrderAddress();
        ReflectionUtil::setId($address, $id);

        return $address;
    }

    private function expectsNoInvocationOfManualEditGranted(): void
    {
        $this->orderAddressSecurityProvider->expects(self::never())
            ->method('isManualEditGranted');
    }

    public function testOnStartCheckoutWhenContextIsNotOfActionDataType(): void
    {
        $this->expectsNoInvocationOfManualEditGranted();

        $event = new ExtendableConditionEvent(new \stdClass());
        $this->listener->onStartCheckout($event);
    }

    public function testOnStartCheckoutWhenCheckoutParameterIsNotOfCheckoutType(): void
    {
        $context = new ActionData(['checkout' => new \stdClass()]);

        $this->expectsNoInvocationOfManualEditGranted();

        $event = new ExtendableConditionEvent($context);
        $this->listener->onStartCheckout($event);
    }

    public function testOnStartCheckoutWhenValidateOnStartCheckoutIsFalse(): void
    {
        $context = new ActionData([
            'checkout' => $this->createMock(Checkout::class),
            'validateOnStartCheckout' => false
        ]);

        $this->expectsNoInvocationOfManualEditGranted();

        $event = new ExtendableConditionEvent($context);
        $this->listener->onStartCheckout($event);
    }

    /**
     * @dataProvider manualEditGrantedDataProvider
     */
    public function testOnStartCheckoutWhenIsApplicableAndManualEditGranted(
        ?bool $shippingManualEdit,
        ?bool $billingManualEdit,
        bool $hasAvailableShippingMethods
    ): void {
        $checkout = $this->createMock(Checkout::class);
        $context = new ActionData(['checkout' => $checkout, 'validateOnStartCheckout' => true]);

        $addressSecurityProviderReturnMap = [];

        if ($shippingManualEdit !== null) {
            $addressSecurityProviderReturnMap[] = [AddressType::TYPE_SHIPPING, $shippingManualEdit];
        }

        if ($billingManualEdit !== null) {
            $addressSecurityProviderReturnMap[] = [AddressType::TYPE_BILLING, $billingManualEdit];
        }

        $this->orderAddressSecurityProvider->expects(self::atLeast(1))
            ->method('isManualEditGranted')
            ->willReturnMap($addressSecurityProviderReturnMap);

        $this->availableShippingMethodChecker->expects(self::once())
            ->method('hasAvailableShippingMethods')
            ->with(self::isInstanceOf(Checkout::class))
            ->willReturn($hasAvailableShippingMethods);

        $event = new ExtendableConditionEvent($context);
        $this->listener->onStartCheckout($event);

        self::assertSame($hasAvailableShippingMethods, $event->getErrors()->isEmpty());
    }

    public function manualEditGrantedDataProvider(): array
    {
        return [
            'shipping manual edit granted and no configs returned' => [
                'shippingManualEdit' => true,
                'billingManualEdit' => false,
                'hasAvailableShippingMethods' => false
            ],
            'billing manual edit granted and method configs returned' => [
                'shippingManualEdit' => false,
                'billingManualEdit' => true,
                'hasAvailableShippingMethods' => true
            ],
            'shipping and billing manual edit granted and method config returned' => [
                'shippingManualEdit' => true,
                'billingManualEdit' => true,
                'hasAvailableShippingMethods' => true
            ],
        ];
    }

    /**
     * @dataProvider notManualEditDataProvider
     */
    public function testOnStartCheckoutWhenIsManualEditNotGranted(
        Checkout $checkout,
        array $customerAddressesMap,
        array $customerUserAddressesMap,
        array $addresses,
        int $expectedCalls,
        array $hasAvailableShippingMethodsCalls
    ): void {
        $context = new ActionData(['checkout' => $checkout, 'validateOnStartCheckout' => true]);

        $this->orderAddressSecurityProvider->expects(self::atLeast(1))
            ->method('isManualEditGranted')
            ->willReturnMap([
                [AddressType::TYPE_SHIPPING, false],
                [AddressType::TYPE_BILLING, false]
            ]);

        $this->addressProvider->expects(self::exactly(count($customerAddressesMap)))
            ->method('getCustomerAddresses')
            ->willReturnMap($customerAddressesMap);

        $this->addressProvider->expects(self::exactly(count($customerUserAddressesMap)))
            ->method('getCustomerUserAddresses')
            ->willReturnMap($customerUserAddressesMap);

        $orderAddress = $this->getOrderAddress(7);

        $this->orderAddressManager->expects(self::exactly($expectedCalls))
            ->method('updateFromAbstract')
            ->withConsecutive(...$addresses)
            ->willReturn($orderAddress);

        $this->availableShippingMethodChecker->expects(self::exactly(count($hasAvailableShippingMethodsCalls)))
            ->method('hasAvailableShippingMethods')
            ->with(self::isInstanceOf(Checkout::class))
            ->willReturnOnConsecutiveCalls(...$hasAvailableShippingMethodsCalls);

        $event = new ExtendableConditionEvent($context);
        $this->listener->onStartCheckout($event);

        self::assertSame(
            !empty(array_filter($hasAvailableShippingMethodsCalls)),
            $event->getErrors()->isEmpty()
        );
    }

    public function notManualEditDataProvider(): array
    {
        $customer = $this->createMock(Customer::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $checkout = new Checkout();
        $checkout->setCustomer($customer);
        $checkout->setCustomerUser($customerUser);

        $shippingCustomerAddress = $this->getOrderAddress(1);
        $shippingCustomerUserAddress = $this->getOrderAddress(3);
        $billingCustomerAddress = $this->getOrderAddress(2);
        $billingCustomerUserAddress = $this->getOrderAddress(4);

        return [
            'has error because no configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_SHIPPING, [$shippingCustomerAddress]],
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]]
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_SHIPPING, [$shippingCustomerUserAddress]],
                    [$customerUser, AddressType::TYPE_BILLING, [$billingCustomerUserAddress]]
                ],
                'addresses' => [
                    [$shippingCustomerAddress],
                    [$shippingCustomerUserAddress],
                    [$billingCustomerAddress],
                    [$billingCustomerUserAddress]
                ],
                'expectedCalls' => 4,
                'hasAvailableShippingMethodsCalls' => [false, false, false, false]
            ],
            'no error because has configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_SHIPPING, []],
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]]
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_SHIPPING, [$shippingCustomerUserAddress]],
                    [$customerUser, AddressType::TYPE_BILLING, []]
                ],
                'addresses' => [[$shippingCustomerUserAddress], [$billingCustomerAddress]],
                'expectedCalls' => 2,
                'hasAvailableShippingMethodsCalls' => [false, true]
            ],
        ];
    }
}
