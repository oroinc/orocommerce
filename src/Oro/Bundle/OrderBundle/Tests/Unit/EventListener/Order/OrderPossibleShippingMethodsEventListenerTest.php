<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\OrderPossibleShippingMethodsEventListener;
use Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
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
     * @var ShippingPricesConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceConverter;

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
        $this->priceConverter = $this->getMockBuilder(ShippingPricesConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new OrderPossibleShippingMethodsEventListener(
            $this->factory,
            $this->priceConverter,
            $this->priceProvider
        );
    }

    /**
     * @dataProvider onOrderEventEmptyKeyDataProvider
     *
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
            ['submittedData' => [OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY => 0]],
            ['submittedData' => [OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY => '0']],
        ];
    }

    /**
     * @dataProvider onOrderEventDataProvider
     *
     * @param ShippingMethodViewCollection $methods
     * @param array|null $submittedData
     * @param array $expectedMethods
     */
    public function testOnOrderEvent(ShippingMethodViewCollection $methods, $submittedData, array $expectedMethods)
    {
        $order = new Order();
        $context = $this->getMock(ShippingContextInterface::class);
        $this->factory->expects(static::any())
            ->method('create')
            ->with($order)
            ->willReturn($context);

        $this->priceConverter->expects(static::any())
            ->method('convertPricesToArray')
            ->with($methods->toArray())
            ->willReturn($expectedMethods);

        $this->priceProvider->expects(static::any())
            ->method('getApplicableMethodsWithTypesData')
            ->with($context)
            ->willReturn($methods);

        $event = new OrderEvent($this->getMock(FormInterface::class), $order, $submittedData);

        $this->listener->onOrderEvent($event);

        static::assertEquals(
            new \ArrayObject(
                [
                    OrderPossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY => $expectedMethods,
                ]
            ),
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
                'methods' =>
                    (new ShippingMethodViewCollection())
                        ->addMethodView('someMethodId', ['sortOrder' => 1])
                        ->addMethodTypeView(
                            'someMethodId',
                            'someTypeId',
                            ['price' => Price::create(10, 'USD')]
                        )
                        ->addMethodTypeView(
                            'someMethodId',
                            'someTypeId2',
                            ['price' => Price::create(11, 'USD')]
                        )
                        ->addMethodView('someMethodId2', ['sortOrder' => 2])
                        ->addMethodTypeView(
                            'someMethodId2',
                            'someTypeId',
                            ['price' => Price::create(12, 'USD')]
                        ),
                'submittedData' => null,
                'expectedMethods' => [
                    'someMethodId' => [
                        'types' => [
                            'someTypeId' => ['price' => ['value' => 10, 'currency' => 'USD']],
                            'someTypeId2' => ['price' => ['value' => 11, 'currency' => 'USD']],
                        ],
                    ],
                    'someMethodId2' => [
                        'types' => [
                            'someTypeId' => ['price' => ['value' => 12, 'currency' => 'USD']],
                        ],
                    ],
                ],
            ],
            'key' => [
                'methods' =>
                    (new ShippingMethodViewCollection())
                        ->addMethodView('someMethodId', ['sortOrder' => 1])
                        ->addMethodTypeView(
                            'someMethodId',
                            'someTypeId',
                            ['price' => Price::create(1, 'USD')]
                        ),
                'submittedData' => [OrderPossibleShippingMethodsEventListener::CALCULATE_SHIPPING_KEY => 'false'],
                'expectedMethods' => [
                    'someMethodId' => [
                        'types' => [
                            'someTypeId' => ['price' => ['value' => 1, 'currency' => 'USD']],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testOnOrderEventWithoutProvider()
    {
        $this->listener = new OrderPossibleShippingMethodsEventListener($this->factory, $this->priceConverter, null);
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
