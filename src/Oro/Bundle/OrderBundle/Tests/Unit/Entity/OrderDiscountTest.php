<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OrderDiscountTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['description', 'Description'],
            ['type', 'test_type'],
            ['amount', 100.00],
            ['percent', 0.1],
            ['order', new Order()]
        ];

        $order = new OrderDiscount();
        $this->assertPropertyAccessors($order, $properties);
    }
}
