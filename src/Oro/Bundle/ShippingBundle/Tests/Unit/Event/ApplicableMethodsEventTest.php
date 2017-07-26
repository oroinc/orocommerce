<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Event;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Event\ApplicableMethodsEvent;

class ApplicableMethodsEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ApplicableMethodsEvent
     */
    protected $applicableMethodsEvent;

    public function testGetters()
    {
        $methodCollection = new ShippingMethodViewCollection();
        $sourceEntity = new \stdClass();

        $this->applicableMethodsEvent = new ApplicableMethodsEvent($methodCollection, $sourceEntity);

        $this->assertSame($methodCollection, $this->applicableMethodsEvent->getMethodCollection());
        $this->assertSame($sourceEntity, $this->applicableMethodsEvent->getSourceEntity());
    }
}
