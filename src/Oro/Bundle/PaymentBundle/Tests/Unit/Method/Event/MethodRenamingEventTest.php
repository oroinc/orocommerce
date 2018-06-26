<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Method\Event;

use Oro\Bundle\PaymentBundle\Method\Event\MethodRenamingEvent;

class MethodRenamingEventTest extends \PHPUnit\Framework\TestCase
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
