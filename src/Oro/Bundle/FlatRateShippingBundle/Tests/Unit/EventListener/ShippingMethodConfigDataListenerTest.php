<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\EventListener;

use Oro\Bundle\FlatRateShippingBundle\EventListener\ShippingMethodConfigDataListener;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethodProvider;
use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;

class ShippingMethodConfigDataListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|FlatRateMethodProvider */
    private $provider;

    /** @var ShippingMethodConfigDataListener */
    private $listener;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder(FlatRateMethodProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new ShippingMethodConfigDataListener($this->provider);
    }

    public function testOnGetConfigDataSetsEventTemplate()
    {
        $methodIdentifier = 'method_1';
        $event = new ShippingMethodConfigDataEvent($methodIdentifier);

        $this->provider
            ->expects(static::once())
            ->method('hasShippingMethod')
            ->with($methodIdentifier)
            ->willReturn(true);

        $this->listener->onGetConfigData($event);

        self::assertEquals(ShippingMethodConfigDataListener::TEMPLATE, $event->getTemplate());
    }
}
