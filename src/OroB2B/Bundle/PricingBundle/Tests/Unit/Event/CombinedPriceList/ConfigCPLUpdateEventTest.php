<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Event\CombinedPriceList;

use OroB2B\Bundle\PricingBundle\Event\CombinedPriceList\ConfigCPLUpdateEvent;

class ConfigCPLUpdateEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $event = new ConfigCPLUpdateEvent();
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\Event', $event);
    }
}
