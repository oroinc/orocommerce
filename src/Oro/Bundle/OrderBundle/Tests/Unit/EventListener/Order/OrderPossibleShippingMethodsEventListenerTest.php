<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderPossibleShippingMethodsEventListener;
use Oro\Bundle\OrderBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class OrderPossibleShippingMethodsEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingContextProviderFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var ShippingPriceProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceProvider;

    /**l
     * @var OrderPossibleShippingMethodsEventListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(ShippingContextProviderFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceProvider = $this->getMockBuilder(ShippingPriceProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new OrderPossibleShippingMethodsEventListener($this->factory, $this->priceProvider);
    }

    public function testOnOrderEvent()
    {
        $context = $this->getMock(ShippingContext::class);
        $this->factory->expects(static::once())
            ->method('create')
            ->willReturn($context);

        $possibleShippingMethods = ['method1', 'method2'];
        $this->priceProvider->expects(static::once())
            ->method('getApplicableMethodsWithTypesData')
            ->willReturn($possibleShippingMethods);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $order = new Order();

        $event = new OrderEvent($form, $order);

        $this->listener->onOrderEvent($event);
        $actualData = $event->getData()->getArrayCopy();

        static::assertArrayHasKey(
            OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY,
            $actualData
        );
        static::assertEquals(
            $possibleShippingMethods,
            $actualData[OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY]
        );
    }
}
