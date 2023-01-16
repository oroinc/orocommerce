<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\PossibleShippingMethodEventListener;
use Oro\Bundle\OrderBundle\Factory\OrderShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;
use Symfony\Component\Form\FormInterface;

class PossibleShippingMethodEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OrderShippingContextFactory|\PHPUnit\Framework\MockObject\MockObject */
    private $factory;

    /** @var ShippingPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $priceProvider;

    /** @var ShippingPricesConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $priceConverter;

    /** @var PossibleShippingMethodEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(OrderShippingContextFactory::class);
        $this->priceProvider = $this->createMock(ShippingPriceProviderInterface::class);
        $this->priceConverter = $this->createMock(ShippingPricesConverter::class);

        $this->listener = new PossibleShippingMethodEventListener(
            $this->factory,
            $this->priceConverter,
            $this->priceProvider
        );
    }

    /**
     * @dataProvider onOrderEventEmptyKeyDataProvider
     */
    public function testOnOrderEventEmptyKey(array $submittedData)
    {
        $order = new Order();
        $this->factory->expects(self::never())
            ->method('create');

        $this->priceProvider->expects(self::never())
            ->method('getApplicableMethodsViews');

        $event = new OrderEvent($this->createMock(FormInterface::class), $order, $submittedData);

        $this->listener->onEvent($event);

        self::assertArrayNotHasKey(
            PossibleShippingMethodEventListener::POSSIBLE_SHIPPING_METHODS_KEY,
            $event->getData()
        );
    }

    public function onOrderEventEmptyKeyDataProvider(): array
    {
        return [
            ['submittedData' => ['field' => 'value']],
            ['submittedData' => [PossibleShippingMethodEventListener::POSSIBLE_SHIPPING_METHODS_KEY => '']],
            ['submittedData' => [PossibleShippingMethodEventListener::POSSIBLE_SHIPPING_METHODS_KEY => 0]],
            ['submittedData' => [PossibleShippingMethodEventListener::POSSIBLE_SHIPPING_METHODS_KEY => '0']],
        ];
    }

    /**
     * @dataProvider onOrderEventDataProvider
     */
    public function testOnOrderEvent(
        ShippingMethodViewCollection $methods,
        ?array $submittedData,
        array $expectedMethods
    ) {
        $order = new Order();
        $context = $this->createMock(ShippingContextInterface::class);
        $this->factory->expects(self::any())
            ->method('create')
            ->with($order)
            ->willReturn($context);

        $this->priceConverter->expects(self::any())
            ->method('convertPricesToArray')
            ->with($methods->toArray())
            ->willReturn($expectedMethods);

        $this->priceProvider->expects(self::any())
            ->method('getApplicableMethodsViews')
            ->with($context)
            ->willReturn($methods);

        $event = new OrderEvent($this->createMock(FormInterface::class), $order, $submittedData);

        $this->listener->onEvent($event);

        self::assertEquals(
            new \ArrayObject(
                [
                    PossibleShippingMethodEventListener::POSSIBLE_SHIPPING_METHODS_KEY => $expectedMethods,
                ]
            ),
            $event->getData()
        );
    }

    public function onOrderEventDataProvider(): array
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
                'submittedData' => [PossibleShippingMethodEventListener::CALCULATE_SHIPPING_KEY => 'false'],
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
}
