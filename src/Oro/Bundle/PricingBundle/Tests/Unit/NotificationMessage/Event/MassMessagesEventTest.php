<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\NotificationMessage\Event;

use Oro\Bundle\PricingBundle\NotificationMessage\Event\MassMessagesEvent;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;

class MassMessagesEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEventMinimal()
    {
        $event = new MassMessagesEvent();
        $this->assertNull($event->getChannel());
        $this->assertNull($event->getTopic());
        $this->assertNull($event->getReceiverEntityFQCN());
        $this->assertNull($event->getReceiverEntityId());
        $this->assertNull($event->getMessages());
    }

    public function testEventFull()
    {
        $message = $this->getMockBuilder(Message::class)
            ->disableOriginalConstructor()
            ->getMock();
        $messages = [$message];
        $event = new MassMessagesEvent(
            'channel',
            'FQCN',
            2,
            'topic',
            $messages
        );
        $this->assertEquals('channel', $event->getChannel());
        $this->assertEquals('topic', $event->getTopic());
        $this->assertEquals('FQCN', $event->getReceiverEntityFQCN());
        $this->assertEquals(2, $event->getReceiverEntityId());
        $this->assertEquals($messages, $event->getMessages());
    }
}
