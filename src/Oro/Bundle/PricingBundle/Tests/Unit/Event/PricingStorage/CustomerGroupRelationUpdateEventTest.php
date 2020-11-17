<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\PricingStorage;

use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerGroupRelationUpdateEvent;

class CustomerGroupRelationUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $data = [
            'websiteId' => 1,
            'customerGroupsIds' => [1, 2, 3]
        ];
        $event = new CustomerGroupRelationUpdateEvent($data);
        $this->assertSame($data, $event->getCustomerGroupsData());
    }
}
