<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Event;

use Oro\Bundle\ShippingBundle\Method\Event\MethodRemovalEvent;

class MethodRemovalEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $methodId = 'method';

        $event = new MethodRemovalEvent($methodId);

        $this->assertSame($methodId, $event->getMethodIdentifier());
    }
}
