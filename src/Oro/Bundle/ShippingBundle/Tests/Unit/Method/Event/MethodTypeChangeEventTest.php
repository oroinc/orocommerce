<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method\Event;

use Oro\Bundle\ShippingBundle\Method\Event\MethodTypeChangeEvent;

class MethodTypeChangeEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $availableTypes = ['1', '2'];
        $methodIdentifier = 'id';
        $errorTypes = ['3', '4'];

        $event = new MethodTypeChangeEvent($availableTypes, $methodIdentifier);

        static::assertSame($availableTypes, $event->getAvailableTypes());
        static::assertSame($methodIdentifier, $event->getMethodIdentifier());
        static::assertSame('oro.shipping.method_type.change.error', $event->getErrorMessagePlaceholder());
        static::assertFalse($event->hasErrors());

        $event->addErrorType($errorTypes[0]);
        $event->addErrorType($errorTypes[1]);

        static::assertTrue($event->hasErrors());
        static::assertSame($errorTypes, $event->getErrorTypes());
    }
}
