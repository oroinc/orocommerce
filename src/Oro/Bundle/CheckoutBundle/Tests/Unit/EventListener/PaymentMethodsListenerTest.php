<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\PaymentMethodsListener;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutPaymentContextProvider;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\ReflectionUtil;

class PaymentMethodsListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderAddressSecurityProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $orderAddressSecurityProvider;

    /** @var OrderAddressManager|\PHPUnit\Framework\MockObject\MockObject */
    private $orderAddressManager;

    /** @var OrderAddressProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $addressProvider;

    /** @var MethodsConfigsRulesByContextProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configsRuleProvider;

    /** @var CheckoutPaymentContextProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutContextProvider;

    /** @var PaymentMethodsListener */
    private $listener;

    protected function setUp(): void
    {
        $this->orderAddressSecurityProvider = $this->createMock(OrderAddressSecurityProvider::class);
        $this->orderAddressManager = $this->createMock(OrderAddressManager::class);
        $this->addressProvider = $this->createMock(OrderAddressProvider::class);
        $this->configsRuleProvider = $this->createMock(MethodsConfigsRulesByContextProviderInterface::class);
        $this->checkoutContextProvider = $this->createMock(CheckoutPaymentContextProvider::class);

        $this->listener = new PaymentMethodsListener(
            $this->addressProvider,
            $this->orderAddressSecurityProvider,
            $this->orderAddressManager,
            $this->configsRuleProvider,
            $this->checkoutContextProvider
        );
    }

    private function getOrderAddress(int $id): OrderAddress
    {
        $address = new OrderAddress();
        ReflectionUtil::setId($address, $id);

        return $address;
    }

    private function getPaymentMethodsConfigsRule(int $id): PaymentMethodsConfigsRule
    {
        $rule = new PaymentMethodsConfigsRule();
        ReflectionUtil::setId($rule, $id);

        return $rule;
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
        array $methodConfigs
    ): void {
        $context = new ActionData([
            'checkout' => $this->createMock(Checkout::class),
            'validateOnStartCheckout' => true
        ]);

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

        $paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->checkoutContextProvider->expects(self::once())
            ->method('getContext')
            ->with($this->isInstanceOf(Checkout::class))
            ->willReturn($paymentContext);
        $this->configsRuleProvider->expects(self::once())
            ->method('getPaymentMethodsConfigsRules')
            ->with($paymentContext)
            ->willReturn($methodConfigs);

        $event = new ExtendableConditionEvent($context);
        $this->listener->onStartCheckout($event);

        self::assertSame(!empty($methodConfigs), $event->getErrors()->isEmpty());
    }

    public function manualEditGrantedDataProvider(): array
    {
        return [
            'manual edit granted and no configs returned' => [
                'shippingManualEdit' => null,
                'billingManualEdit' => true,
                'methodConfigs' => [],
            ],
            'manual edit granted and method configs returned' => [
                'shippingManualEdit' => null,
                'billingManualEdit' => true,
                'methodConfigs' => [
                    $this->getPaymentMethodsConfigsRule(1),
                    $this->getPaymentMethodsConfigsRule(2),
                ],
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
        array $getMethodsConfigsRulesCalls
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

        $paymentContext = $this->createMock(PaymentContextInterface::class);
        $this->checkoutContextProvider->expects(self::exactly($expectedCalls))
            ->method('getContext')
            ->with(self::callback(function (Checkout $checkout) {
                self::assertInstanceOf(OrderAddress::class, $checkout->getBillingAddress());

                return $checkout instanceof Checkout;
            }))
            ->willReturn($paymentContext);
        $this->configsRuleProvider->expects(self::exactly(count($getMethodsConfigsRulesCalls)))
            ->method('getPaymentMethodsConfigsRules')
            ->with($paymentContext)
            ->willReturnOnConsecutiveCalls(...$getMethodsConfigsRulesCalls);

        $event = new ExtendableConditionEvent($context);
        $this->listener->onStartCheckout($event);

        self::assertSame(!empty(array_filter($getMethodsConfigsRulesCalls)), $event->getErrors()->isEmpty());
    }

    public function notManualEditDataProvider(): array
    {
        $customer = $this->createMock(Customer::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $checkout = new Checkout();
        $checkout->setCustomer($customer);
        $checkout->setCustomerUser($customerUser);

        $billingCustomerAddress = $this->getOrderAddress(2);
        $billingCustomerUserAddress = $this->getOrderAddress(4);

        return [
            'error because no configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]],
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_BILLING, [$billingCustomerUserAddress]],
                ],
                'addresses' => [
                    [$billingCustomerAddress],
                    [$billingCustomerUserAddress],
                ],
                'expectedCalls' => 2,
                'getMethodsConfigsRulesCalls' => [[], []],
            ],
            'no error because has configs for customer addresses in provider' => [
                'checkout' => $checkout,
                'customerAddressesMap' => [
                    [$customer, AddressType::TYPE_BILLING, [$billingCustomerAddress]],
                ],
                'customerUserAddressesMap' => [
                    [$customerUser, AddressType::TYPE_BILLING, []],
                ],
                'addresses' => [[$billingCustomerAddress]],
                'expectedCalls' => 1,
                'getMethodsConfigsRulesCalls' => [[$this->getPaymentMethodsConfigsRule(1)]],
            ],
        ];
    }
}
