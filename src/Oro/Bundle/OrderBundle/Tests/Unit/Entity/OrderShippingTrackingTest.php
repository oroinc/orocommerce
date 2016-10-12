<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OrderShippingTrackingTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['method', 'test_method'],
            ['number', '1F2B3C4A'],
            ['order', new Order()]
        ];

        $orderShippingTracking = new OrderShippingTracking();
        $this->assertPropertyAccessors($orderShippingTracking, $properties);
    }
}
