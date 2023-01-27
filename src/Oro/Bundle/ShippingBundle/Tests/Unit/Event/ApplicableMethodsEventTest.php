<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Event;

use Oro\Bundle\ShippingBundle\Event\ApplicableMethodsEvent;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

class ApplicableMethodsEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $methodCollection = new ShippingMethodViewCollection();
        $sourceEntity = new \stdClass();

        $event = new ApplicableMethodsEvent($methodCollection, $sourceEntity);

        $this->assertSame($methodCollection, $event->getMethodCollection());
        $this->assertSame($sourceEntity, $event->getSourceEntity());
    }
}
