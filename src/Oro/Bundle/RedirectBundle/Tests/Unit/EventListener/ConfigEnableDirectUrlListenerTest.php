<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\EventListener\ConfigEnableDirectUrlListener;
use Oro\Bundle\RedirectBundle\Provider\RoutingInformationProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ConfigEnableDirectUrlListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageProducer;

    /**
     * @var RoutingInformationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $provider;

    /**
     * @var ConfigEnableDirectUrlListener
     */
    private $listener;

    protected function setUp()
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->provider = $this->createMock(RoutingInformationProvider::class);

        $this->listener = new ConfigEnableDirectUrlListener($this->messageProducer, $this->provider);
    }

    public function testOnUpdateAfterIsNotChanged()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        $this->provider->expects($this->never())
            ->method($this->anything());
        $this->messageProducer->expects($this->never())
            ->method($this->anything());

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterTurnedOff()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getNewValue')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(false);

        $this->provider->expects($this->once())
            ->method('getEntityClasses')
            ->willReturn(['stdClass']);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::REMOVE_DIRECT_URL_FOR_ENTITY_TYPE, json_encode('stdClass'));

        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterTurnedOn()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);
        $event->expects($this->once())
            ->method('getNewValue')
            ->with('oro_redirect.enable_direct_url')
            ->willReturn(true);

        $this->provider->expects($this->once())
            ->method('getEntityClasses')
            ->willReturn(['stdClass']);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE, json_encode('stdClass'));

        $this->listener->onUpdateAfter($event);
    }
}
