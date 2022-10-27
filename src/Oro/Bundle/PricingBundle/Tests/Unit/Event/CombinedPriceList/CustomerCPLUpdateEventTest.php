<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerCPLUpdateEvent;
use Symfony\Contracts\EventDispatcher\Event;

class CustomerCPLUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $data = [
            'websiteId' => 1,
            'customerIds' => [1, 2, 3]
        ];
        $event = new CustomerCPLUpdateEvent($data);
        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame($data, $event->getCustomersData());
    }
}
