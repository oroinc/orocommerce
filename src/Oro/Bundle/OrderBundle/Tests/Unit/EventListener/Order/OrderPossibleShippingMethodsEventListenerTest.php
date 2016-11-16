<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderPossibleShippingMethodsEventListener;
use Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Symfony\Component\Form\FormInterface;

class OrderPossibleShippingMethodsEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var ShippingPriceProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceProvider;

    /**
     * @var OrderPossibleShippingMethodsEventListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(OrderShippingContextFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceProvider = $this->getMockBuilder(ShippingPriceProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new OrderPossibleShippingMethodsEventListener($this->factory, $this->priceProvider);
    }

    public function testOnOrderEventWithoutKey()
    {
        $order = new Order();
        $this->factory->expects(static::any())
            ->method('create')
            ->with($order)
            ->willReturn($this->getMock(ShippingContextInterface::class));

        $this->priceProvider->expects(static::never())
            ->method('getApplicableMethodsWithTypesData');

        $event = new OrderEvent($this->getMock(FormInterface::class), $order, []);

        $this->listener->onOrderEvent($event);

        static::assertArrayNotHasKey(
            OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY,
            $event->getData()
        );
    }

    public function testOnOrderEvent()
    {
        $order = new Order();
        $context = $this->getMock(ShippingContextInterface::class);
        $this->factory->expects(static::any())
            ->method('create')
            ->with($order)
            ->willReturn($context);

        $possibleShippingMethods = ['method1', 'method2'];
        $this->priceProvider->expects(static::any())
            ->method('getApplicableMethodsWithTypesData')
            ->with($context)
            ->willReturn($possibleShippingMethods);

        $event = new OrderEvent(
            $this->getMock(FormInterface::class),
            $order,
            [OrderPossibleShippingMethodsEventListener::CALCULATE_SHIPPING_KEY => 'true']
        );

        $this->listener->onOrderEvent($event);

        static::assertEquals(
            new \ArrayObject([
                OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY => $possibleShippingMethods
            ]),
            $event->getData()
        );
    }
}
