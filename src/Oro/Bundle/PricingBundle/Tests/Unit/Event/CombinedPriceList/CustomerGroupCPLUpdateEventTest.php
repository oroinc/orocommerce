<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerGroupCPLUpdateEvent;
use Symfony\Contracts\EventDispatcher\Event;

class CustomerGroupCPLUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $data = [
            'websiteId' => 1,
            'customerGroupsIds' => [1, 2, 3]
        ];
        $event = new CustomerGroupCPLUpdateEvent($data);
        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame($data, $event->getCustomerGroupsData());
    }
}
