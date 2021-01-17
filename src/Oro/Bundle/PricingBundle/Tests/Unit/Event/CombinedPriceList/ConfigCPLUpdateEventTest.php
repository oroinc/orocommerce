<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;
use Symfony\Contracts\EventDispatcher\Event;

class ConfigCPLUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $event = new ConfigCPLUpdateEvent();
        $this->assertInstanceOf(Event::class, $event);
    }
}
