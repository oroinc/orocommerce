<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\EventListener;

use Oro\Bundle\ShippingBundle\Event\ShippingMethodConfigDataEvent;
use Oro\Bundle\UPSBundle\EventListener\ShippingMethodConfigDataListener;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethodProvider;

class ShippingMethodConfigDataListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UPSShippingMethodProvider | \PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    /**
     * @var ShippingMethodConfigDataListener
     */
    protected $listener;

    public function setUp()
    {
        $this->provider = $this->getMockBuilder(UPSShippingMethodProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->listener = new ShippingMethodConfigDataListener($this->provider);
    }

    public function testOnGetConfigData()
    {
        $templateName = 'OroUPSBundle::shippingMethodWithOptions.html.twig';
        $methodIdentifier = 'method_1';
        $event = new ShippingMethodConfigDataEvent($methodIdentifier);

        $this->provider
            ->expects(static::once())
            ->method('hasShippingMethod')
            ->with($methodIdentifier)
            ->willReturn(true);

        $this->listener->onGetConfigData($event);

        self::assertEquals($templateName, $event->getTemplate());
    }

    public function testOnGetConfigDataNoMethod()
    {
        $templateName = 'OroUPSBundle::shippingMethodWithOptions.html.twig';
        $methodIdentifier = 'method_1';
        $event = new ShippingMethodConfigDataEvent($methodIdentifier);

        $this->provider
            ->expects(static::once())
            ->method('hasShippingMethod')
            ->with($methodIdentifier)
            ->willReturn(false);

        $this->listener->onGetConfigData($event);

        self::assertEquals('', $event->getTemplate());
    }
}
