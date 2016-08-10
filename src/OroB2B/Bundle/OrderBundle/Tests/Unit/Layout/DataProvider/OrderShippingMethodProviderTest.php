<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Layout\DataProvider\OrderShippingMethodProvider;
use OroB2B\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;

class OrderShippingMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodLabelFormatter;

    /**
     * @var OrderShippingMethodProvider
     */
    protected $orderShippingMethodProvider;

    protected function setUp()
    {
        $this->shippingMethodLabelFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderShippingMethodProvider = new OrderShippingMethodProvider($this->shippingMethodLabelFormatter);
    }

    /**
     * @dataProvider labelsDataProvider
     * @param string $method
     * @param string $type
     * @param string $expected
     */
    public function testGetData($method, $type, $expected)
    {
        $order = new Order();
        $order->setShippingMethod($method)->setShippingMethodType($type);

        /** @var ContextDataCollection|\PHPUnit_Framework_MockObject_MockObject $data */
        $data = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $data->expects($this->once())
            ->method('get')
            ->with('order')
            ->willReturn($order);

        /** @var ContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');
        $context->expects($this->once())
            ->method('data')
            ->willReturn($data);

        $this->shippingMethodLabelFormatter->expects($this->once())
            ->method('formatShippingMethodLabel')
            ->willReturnMap(
                [
                    [null, null],
                    ['flat_rate', 'Flat Rate']
                ]
            );
        $this->shippingMethodLabelFormatter->expects($this->any())
            ->method('formatShippingMethodTypeLabel')
            ->willReturnMap(
                [
                    [null, null, null],
                    ['flat_rate', null, null],
                    ['flat_rate', 'per_order', 'Per Order']
                ]
            );

        $label = $this->orderShippingMethodProvider->getData($context);
        $this->assertEquals($expected, $label);
    }

    /**
     * @return array
     */
    public function labelsDataProvider()
    {
        return [
            'no_method' => [
                'method' => null,
                'type' => null,
                'expected' => null,
            ],
            'no_type' => [
                'method' => 'flat_rate',
                'type' => null,
                'expected' => 'Flat Rate',
            ],
            'method_with_type' => [
                'method' => 'flat_rate',
                'type' => 'per_order',
                'expected' => 'Flat Rate, Per Order',
            ]
        ];
    }
}
