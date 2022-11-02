<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\PricingStorage;

use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerRelationUpdateEvent;

class CustomerRelationUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $data = [
            'websiteId' => 1,
            'customerIds' => [1, 2, 3]
        ];
        $event = new CustomerRelationUpdateEvent($data);
        $this->assertSame($data, $event->getCustomersData());
    }
}
