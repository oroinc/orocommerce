<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Event;

use Oro\Bundle\ShippingBundle\Method\Event\MethodRenamingEvent;

class MethodRenamingEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $oldId = 'old_id';
        $newId = 'new_id';

        $event = new MethodRenamingEvent($oldId, $newId);

        $this->assertSame($oldId, $event->getOldMethodIdentifier());
        $this->assertSame($newId, $event->getNewMethodIdentifier());
    }
}
