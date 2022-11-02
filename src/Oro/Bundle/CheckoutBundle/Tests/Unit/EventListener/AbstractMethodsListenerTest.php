<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\AbstractMethodsListener;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractMethodsListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var OrderAddressSecurityProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $orderAddressSecurityProvider;

    /**
     * @var OrderAddressManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $orderAddressManager;

    /**
     * @var OrderAddressProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $addressProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configsRuleProvider;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutContextProvider;

    /**
     * @var AbstractMethodsListener
     */
    protected $listener;

    protected function setUp(): void
    {
        $this->orderAddressSecurityProvider = $this->getMockBuilder(OrderAddressSecurityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAddressManager = $this->getMockBuilder(OrderAddressManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressProvider = $this->getMockBuilder(OrderAddressProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        unset(
            $this->addressProvider,
            $this->orderAddressManager,
            $this->orderAddressSecurityProvider
        );
    }

    protected function expectsNoInvocationOfManualEditGranted()
    {
        $this->orderAddressSecurityProvider
            ->expects($this->never())
            ->method('isManualEditGranted');
    }

    public function testOnStartCheckoutWhenContextIsNotOfActionDataType()
    {
        $event = new ExtendableConditionEvent(new \stdClass());

        $this->expectsNoInvocationOfManualEditGranted();

        $this->listener->onStartCheckout($event);
    }

    public function testOnStartCheckoutWhenCheckoutParameterIsNotOfCheckoutType()
    {
        $context = new ActionData(['checkout' => new \stdClass()]);
        $event = new ExtendableConditionEvent($context);

        $this->expectsNoInvocationOfManualEditGranted();

        $this->listener->onStartCheckout($event);
    }

    public function testOnStartCheckoutWhenValidateOnStartCheckoutIsFalse()
    {
        $context = new ActionData([
            'checkout' => $this->getEntity(Checkout::class),
            'validateOnStartCheckout' => false
        ]);
        $event = new ExtendableConditionEvent($context);

        $this->expectsNoInvocationOfManualEditGranted();

        $this->listener->onStartCheckout($event);
    }

    /**
     * @return array
     */
    abstract public function manualEditGrantedDataProvider();

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

        $addressSecurityProviderReturnMap = [];

        if ($shippingManualEdit !== null) {
            $addressSecurityProviderReturnMap[] = [AddressType::TYPE_SHIPPING, $shippingManualEdit];
        }

        if ($billingManualEdit !== null) {
            $addressSecurityProviderReturnMap[] = [AddressType::TYPE_BILLING, $billingManualEdit];
        }

        $this->orderAddressSecurityProvider
            ->expects($this->atLeast(1))
            ->method('isManualEditGranted')
            ->willReturnMap($addressSecurityProviderReturnMap);

        $context = $this->createContext();
        $this->checkoutContextProvider
            ->expects($this->once())
            ->method('getContext')
            ->with($this->isInstanceOf(Checkout::class))
            ->willReturn($context);

        $this->configsRuleProvider
            ->expects($this->once())
            ->method($this->getConfigRuleProviderMethod())
            ->with($context)
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
        $paymentContext = $this->createContext();

        $this->checkoutContextProvider
            ->expects($this->exactly($expectedCalls))
            ->method('getContext')
            ->with($this->callback(function (Checkout $checkout) {
                $this->assertInstanceOf(OrderAddress::class, $this->getAddressToCheck($checkout));

                return $checkout instanceof Checkout;
            }))
            ->willReturn($paymentContext);

        $this->configsRuleProvider
            ->expects($this->exactly($expectedCalls))
            ->method($this->getConfigRuleProviderMethod())
            ->with($paymentContext)
            ->willReturnOnConsecutiveCalls(...$willReturnConfigsOnConsecutiveCalls);
    }

    /**
     * @return array
     */
    abstract public function notManualEditDataProvider();

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
            ->expects($this->atLeast(1))
            ->method('isManualEditGranted')
            ->willReturnMap([
                [AddressType::TYPE_SHIPPING, false],
                [AddressType::TYPE_BILLING, false]
            ]);

        $customerAddressCount = count($customerAddressesMap);
        $this->addressProvider
            ->expects($this->exactly($customerAddressCount))
            ->method('getCustomerAddresses')
            ->willReturnMap($customerAddressesMap);

        $customerUserAddressCount = count($customerUserAddressesMap);
        $this->addressProvider
            ->expects($this->exactly($customerUserAddressCount))
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

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    abstract protected function createContext();

    /**
     * @return string
     */
    abstract protected function getConfigRuleProviderMethod();

    /**
     * @param Checkout $checkout
     * @return OrderAddress
     */
    abstract protected function getAddressToCheck(Checkout $checkout);
}
