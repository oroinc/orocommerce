<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderDiscount;

class OrderDiscountTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['description', 'Description'],
            ['type', 'test_type'],
            ['amount', 100],
            ['percent', 0.1],
            ['order', new Order()]
        ];

        $order = new OrderDiscount();
        $this->assertPropertyAccessors($order, $properties);
    }
}
