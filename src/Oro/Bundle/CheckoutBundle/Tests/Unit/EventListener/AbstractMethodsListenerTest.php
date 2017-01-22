<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\AbstractMethodsListener;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\Unit\EntityTrait;

abstract class AbstractMethodsListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var OrderAddressManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $orderAddressManager;

    /**
     * @var AbstractMethodsListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->orderAddressManager = $this->getMockBuilder(OrderAddressManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = $this->getListener($this->orderAddressManager);
    }

    /**
     * @param OrderAddressManager $orderAddressManager
     * @return AbstractMethodsListener
     */
    abstract protected function getListener(OrderAddressManager $orderAddressManager);

    /**
     * @return string
     */
    abstract protected function expectsNoInvocationOfManualEditGranted();

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
}
