<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Event;

use Oro\Bundle\PricingBundle\Event\AbstractProductPricesRemoveEvent;

class AbstractProductPricesRemoveEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
    {
        $args = [
            'product' => new \stdClass()
        ];
        $event = $this->getMockBuilder(AbstractProductPricesRemoveEvent::class)
            ->setConstructorArgs([$args])
            ->getMockForAbstractClass();
        $this->assertEquals($args, $event->getArgs());
    }
}
