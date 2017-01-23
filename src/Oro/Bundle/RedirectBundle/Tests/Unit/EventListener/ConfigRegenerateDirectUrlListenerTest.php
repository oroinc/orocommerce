<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\EventListener\ConfigRegenerateDirectUrlListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ConfigRegenerateDirectUrlListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $messageProducer;

    /**
     * @var string
     */
    private $configParameter;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var ConfigRegenerateDirectUrlListener
     */
    private $listener;

    protected function setUp()
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->configParameter = 'some_parameter';
        $this->entityClass = \stdClass::class;

        $this->listener = new ConfigRegenerateDirectUrlListener(
            $this->messageProducer,
            $this->configParameter,
            $this->entityClass
        );
    }

    public function testOnUpdateAfterIsNotChanged()
    {
        /** @var ConfigUpdateEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(ConfigUpdateEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('isChanged')
            ->with($this->configParameter)
            ->willReturn(false);

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
            ->with($this->configParameter)
            ->willReturn(true);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::REGENERATE_DIRECT_URL_FOR_ENTITY_TYPE, json_encode('stdClass'));

        $this->listener->onUpdateAfter($event);
    }
}
