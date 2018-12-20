<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use Oro\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;

class ConfigCPLUpdateEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $event = new ConfigCPLUpdateEvent();
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
    }
}
