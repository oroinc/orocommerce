<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\CustomerCPLUpdateEvent;

class CustomerCPLUpdateEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $data = [
            'websiteId' => 1,
            'customerIds' => [1, 2, 3]
        ];
        $event = new CustomerCPLUpdateEvent($data);
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
        $this->assertSame($data, $event->getCustomersData());
    }
}
