<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class OrderDiscountTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testProperties(): void
    {
        $properties = [
            ['id', 123],
            ['description', 'Description'],
            ['type', 'test_type'],
            ['amount', 100.00],
            ['percent', 0.1],
            ['order', new Order()],
            ['draftSessionUuid', '8f091a9a-c0d7-4560-975a-d3b0090bcfbd'],
        ];

        $this->assertPropertyAccessors(new OrderDiscount(), $properties);
    }
}
