<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\WebsiteCPLUpdateEvent;
use Symfony\Contracts\EventDispatcher\Event;

class WebsiteCPLUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $data = [1, 2, 3];
        $event = new WebsiteCPLUpdateEvent($data);
        $this->assertInstanceOf(Event::class, $event);
        $this->assertSame($data, $event->getWebsiteIds());
    }
}
