<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\EventListener\Order\OrderTaxesListener;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;

class OrderTaxesListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrderTaxesListener */
    protected $listener;

    /** @var TaxManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $taxManager;

    /** @var OrderEvent|\PHPUnit_Framework_MockObject_MockObject  */
    protected $event;

    /** @var NumberFormatter */
    protected $numberFormatter;

    protected function setUp()
    {
        $this->taxManager = $this->getMockBuilder('OroB2B\Bundle\TaxBundle\Manager\TaxManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Event\OrderEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $this->numberFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NumberFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new OrderTaxesListener($this->taxManager, $this->numberFormatter);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->taxManager, $this->event);
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

        $this->numberFormatter->expects($this->any())
            ->method('formatPercent')
            ->willReturn('10%');

        $this->event->expects($this->once())
            ->method('getOrder')
            ->willReturn($order);
        $this->event->expects($this->exactly(2))
            ->method('getData')
            ->willReturn($data);

        $this->listener->OnOrderEvent($this->event);

        $this->assertEquals($data->getArrayCopy(), $expectedResult);
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
                    'taxesTotal' => [
                        'includingTax' => 55,
                        'excludingTax' => 50,
                        'taxAmount' => 5,
                        'adjustment' => 0
                    ],
                    'taxesItems' => [
                        [
                            'unit' => [
                                'includingTax' => 11,
                                'excludingTax' => 10,
                                'taxAmount' => 1,
                                'adjustment' => 0
                            ],
                            'row' => [
                                'includingTax' => 55,
                                'excludingTax' => 50,
                                'taxAmount' => 5,
                                'adjustment' => 0
                            ],
                            'taxes' => [
                                [
                                    'tax' => 'TAX',
                                    'rate' => '10%',
                                    'taxableAmount' => 50,
                                    'taxAmount' => 5,
                                    'currency' => 'USD'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
