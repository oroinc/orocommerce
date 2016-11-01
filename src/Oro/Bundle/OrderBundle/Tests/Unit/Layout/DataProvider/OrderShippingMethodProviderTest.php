<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Layout\DataProvider\OrderShippingMethodProvider;
use Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter;

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
        if (class_exists('Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter')) {
            $this->shippingMethodLabelFormatter = $this
                ->getMockBuilder('Oro\Bundle\ShippingBundle\Formatter\ShippingMethodLabelFormatter')
                ->disableOriginalConstructor()
                ->getMock();
        } else {
            $this->shippingMethodLabelFormatter = null;
        }
        $this->orderShippingMethodProvider = new OrderShippingMethodProvider($this->shippingMethodLabelFormatter);
    }

    public function testGetData()
    {
        $method = 'Some Method';
        $type = 'Some Type';
        $expected = sprintf('%s, %s', $method, $type);
        $order = new Order();
        $order->setShippingMethod($method)->setShippingMethodType($type);

        if ($this->shippingMethodLabelFormatter) {
            $this->shippingMethodLabelFormatter->expects($this->once())
                ->method('formatShippingMethodWithType')
                ->with($method, $type)
                ->willReturn($expected);
        }
        $label = $this->orderShippingMethodProvider->getData($order);
        $this->assertEquals($expected, $label);
    }
}
