<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\PricingStorage;

use Oro\Bundle\PricingBundle\Event\PricingStorage\WebsiteRelationUpdateEvent;

class WebsiteRelationUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $data = [1, 2, 3];
        $event = new WebsiteRelationUpdateEvent($data);
        $this->assertSame($data, $event->getWebsiteIds());
    }
}
