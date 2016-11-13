<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Layout\DataProvider\OrderShippingMethodProvider;
use Oro\Bundle\OrderBundle\Formatter\ShippingMethodFormatter;

class OrderShippingMethodProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShippingMethodFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shippingMethodFormatter;

    /**
     * @var OrderShippingMethodProvider
     */
    protected $orderShippingMethodProvider;

    protected function setUp()
    {
        $this->shippingMethodFormatter = $this
            ->getMockBuilder('Oro\Bundle\OrderBundle\Formatter\ShippingMethodFormatter')
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderShippingMethodProvider = new OrderShippingMethodProvider($this->shippingMethodFormatter);
    }

    public function testGetData()
    {
        $method = 'Some Method';
        $type = 'Some Type';
        $expected = sprintf('%s, %s', $method, $type);
        $order = new Order();
        $order->setShippingMethod($method)->setShippingMethodType($type);


        $this->shippingMethodFormatter->expects($this->once())
            ->method('formatShippingMethodWithTypeLabel')
            ->with($method, $type)
            ->willReturn($expected);

        $label = $this->orderShippingMethodProvider->getData($order);
        $this->assertEquals($expected, $label);
    }
}
