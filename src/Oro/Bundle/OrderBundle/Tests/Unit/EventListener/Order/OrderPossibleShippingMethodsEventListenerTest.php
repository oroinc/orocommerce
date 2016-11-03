<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderPossibleShippingMethodsEventListener;
use Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

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

    /**l
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

    /**
     * @dataProvider onOrderEventDataProvider
     * @param array $submitted
     * @param bool $hasKey
     */
    public function testOnOrderEvent($submitted, $hasKey)
    {
        $order = new Order();
        $context = $this->getMock(ShippingContext::class);
        $this->factory->expects(static::any())
            ->method('create')
            ->with($order)
            ->willReturn($context);

        $possibleShippingMethods = ['method1', 'method2'];
        $this->priceProvider->expects(static::any())
            ->method('getApplicableMethodsWithTypesData')
            ->with($context)
            ->willReturn($possibleShippingMethods);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $event = new OrderEvent(
            $form,
            $order,
            $submitted
        );

        $this->listener->onOrderEvent($event);
        $actualData = $event->getData()->getArrayCopy();

        if ($hasKey) {
            static::assertArrayHasKey(
                OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY,
                $actualData
            );
            static::assertEquals(
                $possibleShippingMethods,
                $actualData[OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY]
            );
        } else {
            static::assertArrayNotHasKey(
                OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY,
                $actualData
            );
        }
    }

    public function onOrderEventDataProvider()
    {
        return [
            [
                'submitted' => [],
                'hasKey' => false,
            ],
            [
                'submitted' => [OrderPossibleShippingMethodsEventListener::CALCULATE_SHIPPING_KEY => 'false'],
                'hasKey' => false,
            ],
            [
                'submitted' => [OrderPossibleShippingMethodsEventListener::CALCULATE_SHIPPING_KEY => 'true'],
                'hasKey' => true,
            ],
        ];
    }
}
