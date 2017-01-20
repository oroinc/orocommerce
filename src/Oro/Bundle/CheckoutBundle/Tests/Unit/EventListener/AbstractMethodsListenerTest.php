<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\AbstractMethodsListener;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressProvider;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractMethodsListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var OrderAddressProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $addressProvider;

    /** @var OrderAddressSecurityProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $orderAddressSecurityProvider;

    /** @var OrderAddressManager|\PHPUnit_Framework_MockObject_MockObject */
    private $orderAddressManager;

    /**
     * @var AbstractMethodsListener
     */
    private $listener;

    protected function setUp()
    {
        $this->addressProvider = $this->getMockBuilder(OrderAddressProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAddressSecurityProvider = $this->getMockBuilder(OrderAddressSecurityProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderAddressManager = $this->getMockBuilder(OrderAddressManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = $this->getListener(
            $this->addressProvider,
            $this->orderAddressSecurityProvider,
            $this->orderAddressManager
        );
    }

    /**
     * @param OrderAddressProvider $addressProvider
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param OrderAddressManager $orderAddressManager
     * @return AbstractMethodsListener
     */
    abstract protected function getListener(
        OrderAddressProvider $addressProvider,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        OrderAddressManager $orderAddressManager
    );

    /**
     * @return string
     */
    abstract protected function getAddressType();

    public function testOnStartCheckoutWhenContextIsNotOfActionDataType()
    {
        $event = new ExtendableConditionEvent(new \stdClass());

        $this->orderAddressSecurityProvider
            ->expects($this->never())
            ->method('isManualEditGranted');

        $this->listener->onStartCheckout($event);
    }

    public function testOnStartCheckoutWhenCheckoutParameterIsNotOfCheckoutType()
    {
        $context = new ActionData(['checkout' => new \stdClass()]);
        $event = new ExtendableConditionEvent($context);

        $this->orderAddressSecurityProvider
            ->expects($this->never())
            ->method('isManualEditGranted');

        $this->listener->onStartCheckout($event);
    }

    public function testOnStartCheckoutWhenValidateOnStartCheckoutIsFalse()
    {
        $context = new ActionData([
            'checkout' => $this->getEntity(Checkout::class),
            'validateOnStartCheckout' => false
        ]);
        $event = new ExtendableConditionEvent($context);

        $this->orderAddressSecurityProvider
            ->expects($this->never())
            ->method('isManualEditGranted');

        $this->listener->onStartCheckout($event);
    }

    /**
     * @param array $willReturnConfigs
     */
    abstract protected function expectsHasMethodsConfigsWithoutAddressInvocation(array $willReturnConfigs);

    /**
     * @param int $expectedCalls
     * @param array $willReturnConfigsOnConsecutiveCalls
     */
    abstract protected function expectsHasMethodsConfigsForAddressesInvocation(
        $expectedCalls,
        array $willReturnConfigsOnConsecutiveCalls
    );

    /**
     * @param array $params
     * @return mixed
     */
    abstract protected function getMethodConfig(array $params);

    /**
     * @return array
     */
    public function manualEditDataProvider()
    {
        return [
            'has method configs' => [
                'hasErrors' => false,
                'methodConfigs' => [$this->getMethodConfig(['id' => 1]), $this->getMethodConfig(['id' => 2])]
            ],
            'has no method configs' => [
                'hasErrors' => true,
                'methodConfigs' => []
            ],
        ];
    }

    /**
     * @dataProvider manualEditDataProvider
     *
     * @param bool $hasErrors
     * @param array $methodConfigs
     */
    public function testOnStartCheckoutWhenIsManualEditGranted($hasErrors, array $methodConfigs)
    {
        $context = new ActionData(['checkout' => $this->getEntity(Checkout::class), 'validateOnStartCheckout' => true]);
        $event = new ExtendableConditionEvent($context);

        $this->orderAddressSecurityProvider
            ->expects($this->once())
            ->method('isManualEditGranted')
            ->willReturn(true);

        $this->expectsHasMethodsConfigsWithoutAddressInvocation($methodConfigs);

        $this->listener->onStartCheckout($event);

        $this->assertEquals($hasErrors, !$event->getErrors()->isEmpty());
    }

    /**
     * @return array
     */
    public function notManualEditDataProvider()
    {
        $customerAddress = $this->getEntity(OrderAddress::class, ['id' => 1]);
        $customerUserAddress = $this->getEntity(OrderAddress::class, ['id' => 3]);

        return [
            'no configs for customer addresses in provider' => [
                'hasErrors' => true,
                'customerAddresses' => [$customerAddress],
                'customerUserAddresses' => [$customerUserAddress],
                'consecutiveAddresses' => [[$customerAddress], [$customerUserAddress]],
                'expectedCalls' => 2,
                'onConsecutiveMethodConfigs' => [[], []]
            ],
            'has configs for customer addresses in provider' => [
                'hasErrors' => false,
                'customerAddresses' => [$customerAddress],
                'customerUserAddresses' => [$customerUserAddress],
                'consecutiveAddresses' => [[$customerAddress], [$customerUserAddress]],
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
     * @param bool $hasErrors
     * @param array $customerAddresses
     * @param array $customerUserAddresses
     * @param array $consecutiveAddresses
     * @param int $expectedCalls
     * @param array $onConsecutiveMethodConfigs
     */
    public function testOnStartCheckoutWhenIsManualEditNotGranted(
        $hasErrors,
        array $customerAddresses,
        array $customerUserAddresses,
        array $consecutiveAddresses,
        $expectedCalls,
        array $onConsecutiveMethodConfigs
    ) {
        $customer = $this->getEntity(Customer::class);
        $customerUser = $this->getEntity(CustomerUser::class);
        $context = new ActionData([
            'checkout' => $this->getEntity(Checkout::class, [
                'customer' => $customer,
                'customerUser' => $customerUser,
            ]),
            'validateOnStartCheckout' => true
        ]);

        $event = new ExtendableConditionEvent($context);

        $this->orderAddressSecurityProvider
            ->expects($this->once())
            ->method('isManualEditGranted')
            ->willReturn(false);

        $this->addressProvider
            ->expects($this->once())
            ->method('getCustomerAddresses')
            ->with($customer, $this->getAddressType())
            ->willReturn($customerAddresses);

        $this->addressProvider
            ->expects($this->once())
            ->method('getCustomerUserAddresses')
            ->with($customerUser, $this->getAddressType())
            ->willReturn($customerUserAddresses);

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
