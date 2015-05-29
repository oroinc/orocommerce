<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Event;

use OroB2B\Bundle\PricingBundle\Event\AbstractProductPricesRemoveEvent;

class AbstractProductPricesRemoveEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $args = [
            'product' => new \stdClass()
        ];
        /** @var AbstractProductPricesRemoveEvent $event */
        $event = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Event\AbstractProductPricesRemoveEvent')
            ->setConstructorArgs([$args])
            ->getMockForAbstractClass();
        $this->assertEquals($args, $event->getArgs());
    }
}
