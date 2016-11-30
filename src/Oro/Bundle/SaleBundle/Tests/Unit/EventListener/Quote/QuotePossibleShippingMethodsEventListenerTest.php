<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\EventListener\Quote;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Converter\ShippingPricesConverter;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Event\QuoteEvent;
use Oro\Bundle\SaleBundle\EventListener\Quote\QuotePossibleShippingMethodsEventListener;
use Oro\Bundle\SaleBundle\Factory\QuoteShippingContextFactory;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Symfony\Component\Form\FormInterface;

class QuotePossibleShippingMethodsEventListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QuoteShippingContextFactory|\PHPUnit_Framework_MockObject_MockObject
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
        $this->factory = $this->getMockBuilder(QuoteShippingContextFactory::class)
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
            ->method('getApplicableMethodsWithTypesData');

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
     * @param array $methods
     * @param array|null $submittedData
     * @param array $expectedMethods
     */
    public function testOnOrderEvent(array $methods, $submittedData, array $expectedMethods)
    {
        $quote = new Quote();
        $context = $this->getMock(ShippingContextInterface::class);
        $this->factory->expects(static::any())
            ->method('create')
            ->with($quote)
            ->willReturn($context);

        $this->priceConverter->expects(static::any())
            ->method('convertPricesToArray')
            ->with($methods)
            ->willReturn($expectedMethods);

        $this->priceProvider->expects(static::any())
            ->method('getApplicableMethodsWithTypesData')
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
                'submittedData' => [QuotePossibleShippingMethodsEventListener::CALCULATE_SHIPPING_KEY => 'false'],
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
        $this->listener = new QuotePossibleShippingMethodsEventListener($this->factory, $this->priceConverter, null);
        $quote = new Quote();
        $this->factory->expects(static::never())
            ->method('create');

        $this->priceProvider->expects(static::never())
            ->method('getApplicableMethodsWithTypesData');

        $methods = ['field' => 'value'];
        $event = new QuoteEvent($this->getMock(FormInterface::class), $quote, $methods);

        $this->listener->onQuoteEvent($event);

        static::assertArrayNotHasKey(
            QuotePossibleShippingMethodsEventListener::POSSIBLE_SHIPPING_METHODS_KEY,
            $event->getData()
        );
    }
}
