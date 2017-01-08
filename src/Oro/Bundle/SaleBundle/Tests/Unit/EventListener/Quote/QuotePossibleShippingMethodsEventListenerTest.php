<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Quote;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\EventListener\Quote\QuotePossibleShippingMethodsEventListener;
use Oro\Bundle\SaleBundle\Factory\QuoteShippingContextFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Symfony\Component\Form\FormInterface;

class QuotePossibleShippingMethodsEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteShippingContextFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
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
     * @var QuotePossibleShippingMethodsEventListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(QuoteShippingContextFactoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceProvider = $this->getMockBuilder(ShippingPriceProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceConverter = $this->getMockBuilder(ShippingPricesConverter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new QuotePossibleShippingMethodsEventListener(
            $this->factory,
            $this->priceConverter,
            $this->priceProvider
        );
    }

    /**
     * @dataProvider onOrderEventEmptyKeyDataProvider
     * @param array $submittedData
     */
    public function testOnOrderEventEmptyKey(array $submittedData)
    {
        $quote = new Quote();
        $this->factory->expects(static::never())
            ->method('create');

        $this->priceProvider->expects(static::never())
            ->method('getApplicableMethodsViews');

        $event = new QuoteEvent($this->getMock(FormInterface::class), $quote, $submittedData);

        $this->listener->onQuoteEvent($event);

        static::assertArrayNotHasKey(
            QuotePossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY,
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
            ['submittedData' => [QuotePossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY => '']],
            ['submittedData' => [QuotePossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY => 0]],
            ['submittedData' => [QuotePossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY => '0']],
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
        $quote = new Quote();
        $context = $this->getMock(ShippingContextInterface::class);
        $this->factory->expects(static::any())
            ->method('create')
            ->with($quote)
            ->willReturn($context);

        $this->priceConverter->expects(static::any())
            ->method('convertPricesToArray')
            ->with($methods->toArray())
            ->willReturn($expectedMethods);

        $this->priceProvider->expects(static::any())
            ->method('getApplicableMethodsViews')
            ->with($context)
            ->willReturn($methods);

        $event = new QuoteEvent($this->getMock(FormInterface::class), $quote, $submittedData);

        $this->listener->onQuoteEvent($event);

        static::assertEquals(
            new \ArrayObject([
                QuotePossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY => $expectedMethods
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
                'submittedData' => [QuotePossibleShippingMethodsEventListener::CALCULATE_SHIPPING_KEY => 'false'],
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
