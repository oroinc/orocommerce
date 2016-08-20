<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event;

use Oro\Bundle\PricingBundle\Event\AbstractProductPricesRemoveEvent;

class AbstractProductPricesRemoveEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $args = [
            'product' => new \stdClass()
        ];
        /** @var AbstractProductPricesRemoveEvent $event */
        $event = $this->getMockBuilder('Oro\Bundle\PricingBundle\Event\AbstractProductPricesRemoveEvent')
            ->setConstructorArgs([$args])
            ->getMockForAbstractClass();
        $this->assertEquals($args, $event->getArgs());
    }
}
