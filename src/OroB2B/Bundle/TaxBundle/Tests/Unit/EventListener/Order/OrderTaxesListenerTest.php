<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener\Order;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\EventListener\Order\MatchingPriceEventListener;
use OroB2B\Bundle\OrderBundle\Pricing\PriceMatcher;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\EventListener\Order\OrderTaxesListener;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderTaxesListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var OrderTaxesListener */
    protected $listener;

    /** @var TaxManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $taxManager;

    /** @var OrderEvent|\PHPUnit_Framework_MockObject_MockObject */
    protected $event;

    /** @var PriceMatcher|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceMatcher;

    /** @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $taxationSettingsProvider;

    protected function setUp()
    {
        $this->taxManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Event\OrderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->taxationSettingsProvider = $this
            ->getMockBuilder('OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceMatcher = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Pricing\PriceMatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new OrderTaxesListener(
            $this->taxManager,
            $this->taxationSettingsProvider,
            $this->priceMatcher
        );
    }

    protected function tearDown()
    {
        unset($this->listener, $this->taxManager, $this->event, $this->numberFormatter);
    }

    /**
     * @param Result $result
     * @param array $expectedResult
     *
     * @dataProvider onOrderEventDataProvider
     */
    public function testOnOrderEvent(Result $result, array $expectedResult)
    {
        $order = new Order();
        $data = new \ArrayObject();

        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->with($order)
            ->willReturn($result);

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);

        $this->event->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);

        $this->event->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $this->listener->onOrderEvent($this->event);

        $this->assertEquals($data->getArrayCopy(), $expectedResult);
    }

    public function testOnOrderEventTaxationDisabled()
    {
        $this->taxManager->expects($this->never())->method($this->anything());
        $this->event->expects($this->never())->method($this->anything());

        $this->taxationSettingsProvider->expects($this->once())
            ->method('isEnabled')
            ->willReturn(false);

        $this->listener->onOrderEvent($this->event);
    }

    /**
     * @dataProvider orderEventAddMatchedPricesDataProvider
     * @param array $orderLineItems
     * @param array $matchedPrices
     * @param array $expectedLineItemsPrices
     */
    public function testOnOrderEventAddMatchedPrices(
        array $orderLineItems = [],
        array $matchedPrices = [],
        array $expectedLineItemsPrices = []
    ) {
        $order = new Order();
        $data = new \ArrayObject();
        $data->offsetSet(MatchingPriceEventListener::MATCHED_PRICES_KEY, $matchedPrices);

        array_walk(
            $orderLineItems,
            function (OrderLineItem $orderLineItem) use ($order) {
                $order->addLineItem($orderLineItem);
            }
        );

        $this->event->expects($this->once())->method('getOrder')->willReturn($order);
        $this->event->expects($this->once())->method('getData')->willReturn($data);

        $this->taxationSettingsProvider->expects($this->once())->method('isEnabled')->willReturn(true);

        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->with(
                $this->callback(
                    function (Order $order) use ($expectedLineItemsPrices) {
                        foreach ($order->getLineItems() as $key => $orderLineItem) {
                            $this->assertArrayHasKey($key, $expectedLineItemsPrices);
                            $this->assertEquals($expectedLineItemsPrices[$key], $orderLineItem->getValue());
                        }

                        return true;
                    }
                )
            )
            ->willReturn(new Result());

        $this->listener->onOrderEvent($this->event);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function orderEventAddMatchedPricesDataProvider()
    {
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 1]);
        $invalidProduct = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product');
        $productUnit = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit', ['code' => 'set']);
        $invalidProductUnit = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');

        return [
            'empty prices' => [],
            'no matched prices' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'without product' => [
                [
                    (new OrderLineItem())
                        ->setProductUnit($productUnit)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid product' => [
                [
                    (new OrderLineItem())
                        ->setProduct($invalidProduct)
                        ->setProductUnit($productUnit)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'without productUnit' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid productUnit' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($invalidProductUnit)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'without currency' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProduct($product)
                        ->setQuantity('3'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid currency' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProduct($product)
                        ->setQuantity('3')
                        ->setCurrency(''),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'without quantity' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProduct($product)
                        ->setCurrency('USD'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid quantity #1' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProduct($product)
                        ->setQuantity('string')
                        ->setCurrency('USD'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'invalid quantity #2' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProduct($product)
                        ->setQuantity(-2)
                        ->setCurrency('USD'),
                ],
                ['1-set-2-USD' => ['currency' => 'USD', 'value' => 100]],
                [null],
            ],
            'one matched price' => [
                [
                    (new OrderLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setCurrency('USD')
                        ->setQuantity('3'),
                ],
                [
                    '1-set-2-USD' => ['currency' => 'USD', 'value' => 100],
                    '1-set-3-USD' => ['currency' => 'USD', 'value' => 150],
                ],
                ['150'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function onOrderEventDataProvider()
    {
        $taxResult = TaxResultElement::create('TAX', 0.1, 50, 5);
        $taxResult->offsetSet(TaxResultElement::CURRENCY, 'USD');

        $lineItem = new Result();
        $lineItem->offsetSet(Result::UNIT, ResultElement::create(11, 10, 1, 0));
        $lineItem->offsetSet(Result::ROW, ResultElement::create(55, 50, 5, 0));
        $lineItem->offsetSet(Result::TAXES, [$taxResult]);

        $result = new Result();
        $result->offsetSet(Result::TOTAL, ResultElement::create(55, 50, 5, 0));
        $result->offsetSet(Result::ITEMS, [$lineItem]);
        $result->offsetSet(Result::TAXES, [$taxResult]);

        return [
            [
                $result,
                [
                    'taxItems' => [
                        [
                            'unit' => [
                                'includingTax' => 11,
                                'excludingTax' => 10,
                                'taxAmount' => 1,
                                'adjustment' => 0,
                            ],
                            'row' => [
                                'includingTax' => 55,
                                'excludingTax' => 50,
                                'taxAmount' => 5,
                                'adjustment' => 0,
                            ],
                            'taxes' => [
                                [
                                    'tax' => 'TAX',
                                    'rate' => '0.1',
                                    'taxableAmount' => '50',
                                    'taxAmount' => '5',
                                    'currency' => 'USD',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
