<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\CurrencyBundle\Entity\Price;
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

    /**
     * @dataProvider onOrderEventEmptyKeyDataProvider
     * @param array $submittedData
     */
    public function testOnOrderEventEmptyKey(array $submittedData)
    {
        $order = new Order();
        $this->factory->expects(static::never())
            ->method('create');

        $this->priceProvider->expects(static::never())
            ->method('getApplicableMethodsWithTypesData');

        $event = new OrderEvent($this->getMock(FormInterface::class), $order, $submittedData);

        $this->listener->onOrderEvent($event);

        static::assertArrayNotHasKey(
            OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY,
            $event->getData()
        );
    }

    /**
     * @return array
     */
    public function onOrderEventEmptyKeyDataProvider()
    {
        return [
            ['submittedData' => ['field' => 'value']],
            ['submittedData' => [OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY => '']],
        ];
    }

    /**
     * @dataProvider onOrderEventDataProvider
     *
     * @param array $methods
     * @param array|null $submittedData
     * @param array $expectedMethods
     */
    public function testOnOrderEvent(array $methods, $submittedData, array $expectedMethods)
    {
        $order = new Order();
        $context = $this->getMock(ShippingContextInterface::class);
        $this->factory->expects(static::any())
            ->method('create')
            ->with($order)
            ->willReturn($context);

        $this->priceProvider->expects(static::any())
            ->method('getApplicableMethodsWithTypesData')
            ->with($context)
            ->willReturn($methods);

        $event = new OrderEvent($this->getMock(FormInterface::class), $order, $submittedData);

        $this->listener->onOrderEvent($event);

        static::assertEquals(
            new \ArrayObject([
                OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY => $expectedMethods
            ]),
            $event->getData()
        );
    }

    /**
     * @return array
     */
    public function onOrderEventDataProvider()
    {
        return [
            'null submitted data' => [
                'methods' => [
                    [
                        'types' => [
                            ['price' => Price::create(10, 'USD')],
                            ['price' => Price::create(11, 'USD')],
                        ]
                    ],
                    [
                        'types' => [
                            ['price' => Price::create(12, 'USD')],
                        ]
                    ]
                ],
                'submittedData' => null,
                'expectedMethods' => [
                    [
                        'types' => [
                            ['price' => ['value' => 10, 'currency' => 'USD']],
                            ['price' => ['value' => 11, 'currency' => 'USD']],
                        ]
                    ],
                    [
                        'types' => [
                            ['price' => ['value' => 12, 'currency' => 'USD']],
                        ]
                    ]
                ],
            ],
            'key' => [
                'methods' => [
                    [
                        'types' => [
                            ['price' => Price::create(1, 'USD')],
                        ]
                    ]
                ],
                'submittedData' => [OrderPossibleShippingMethodsEventListener::CALCULATE_SHIPPING_KEY => 'false'],
                'expectedMethods' => [
                    [
                        'types' => [
                            ['price' => ['value' => 1, 'currency' => 'USD']],
                        ]
                    ]
                ],
            ],
        ];
    }

    public function testOnOrderEventWithoutProvider()
    {
        $this->listener = new OrderPossibleShippingMethodsEventListener($this->factory);
        $order = new Order();
        $this->factory->expects(static::never())
            ->method('create');

        $this->priceProvider->expects(static::never())
            ->method('getApplicableMethodsWithTypesData');

        $methods = ['field' => 'value'];
        $event = new OrderEvent($this->getMock(FormInterface::class), $order, $methods);

        $this->listener->onOrderEvent($event);

        static::assertArrayNotHasKey(
            OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY,
            $event->getData()
        );
    }
}
