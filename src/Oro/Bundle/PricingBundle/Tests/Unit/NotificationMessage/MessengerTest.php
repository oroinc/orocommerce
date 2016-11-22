<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\NotificationMessage;

use Oro\Bundle\PricingBundle\NotificationMessage\Event\MassMessagesEvent;
use Oro\Bundle\PricingBundle\NotificationMessage\Event\MessageEvent;
use Oro\Bundle\PricingBundle\NotificationMessage\Event\MessageEvents;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\PricingBundle\NotificationMessage\Messenger;
use Oro\Bundle\PricingBundle\NotificationMessage\Transport\TransportInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MessengerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TransportInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sender;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var Messenger
     */
    protected $messenger;

    protected function setUp()
    {
        $this->sender = $this->getMock(TransportInterface::class);
        $this->eventDispatcher = $this->getMock(EventDispatcherInterface::class);
        $this->messenger = new Messenger($this->sender, $this->eventDispatcher);
    }

    public function testAddTransport()
    {
        /** @var TransportInterface|\PHPUnit_Framework_MockObject_MockObject $transport * */
        $transport = $this->getMock(TransportInterface::class);
        $this->assertAttributeCount(1, 'transports', $this->messenger);
        $this->assertAttributeContains($this->sender, 'transports', $this->messenger);

        $this->messenger->addTransport($transport);
        $this->assertAttributeCount(2, 'transports', $this->messenger);
        $this->assertAttributeContains($transport, 'transports', $this->messenger);

        // Check that transpor was not added twice
        $this->messenger->addTransport($transport);
        $this->assertAttributeCount(2, 'transports', $this->messenger);
    }

    public function testSend()
    {
        $channel = 'channel';
        $topic = 'topic';
        $status = 'status';
        $messageText = 'message';
        $receiverEntityFQCN = 'FQCN';
        $receiverEntityId = 2;

        $message = new Message($channel, $topic, $messageText, $status, $receiverEntityFQCN, $receiverEntityId);
        $messageEvent = new MessageEvent($message);

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [MessageEvents::BEFORE_SEND, $messageEvent],
                [MessageEvents::AFTER_SEND, $messageEvent]
            );

        $this->sender->expects($this->once())
            ->method('send')
            ->with($message);

        $this->messenger->send($channel, $topic, $status, $messageText, $receiverEntityFQCN, $receiverEntityId);
    }

    public function testReceiveFromSender()
    {
        $channel = 'channel';
        $receiverEntityFQCN = 'FQCN';
        $receiverEntityId = 2;
        $topic = 'topic';

        $message1 = new Message($channel, $topic, 'msg1', 'success', $receiverEntityFQCN, $receiverEntityId);

        $this->sender->expects($this->once())
            ->method('receive')
            ->with($channel, $receiverEntityFQCN, $receiverEntityId, $topic)
            ->willReturn([$message1]);

        $messages = [$message1];
        $event = new MassMessagesEvent($channel, $receiverEntityFQCN, $receiverEntityId, $topic, $messages);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(MessageEvents::ON_RECEIVE, $event);

        $this->assertEquals(
            $messages,
            $this->messenger->receive($channel, $receiverEntityFQCN, $receiverEntityId, $topic)
        );
    }

    public function testReceive()
    {
        $channel = 'channel';
        $receiverEntityFQCN = 'FQCN';
        $receiverEntityId = 2;
        $topic = 'topic';

        $message1 = new Message($channel, $topic, 'msg1', 'success', $receiverEntityFQCN, $receiverEntityId);
        $message2 = new Message($channel, $topic, 'msg2', 'info', $receiverEntityFQCN, $receiverEntityId);

        $this->sender->expects($this->once())
            ->method('receive')
            ->with($channel, $receiverEntityFQCN, $receiverEntityId, $topic)
            ->willReturn([$message1]);

        /** @var TransportInterface|\PHPUnit_Framework_MockObject_MockObject $transport * */
        $transport = $this->getMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('receive')
            ->with($channel, $receiverEntityFQCN, $receiverEntityId, $topic)
            ->willReturn([$message2]);

        $this->messenger->addTransport($transport);

        $messages = [$message1, $message2];
        $event = new MassMessagesEvent($channel, $receiverEntityFQCN, $receiverEntityId, $topic, $messages);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(MessageEvents::ON_RECEIVE, $event);

        $this->assertEquals(
            $messages,
            $this->messenger->receive($channel, $receiverEntityFQCN, $receiverEntityId, $topic)
        );
    }

    public function testRemoveFromSender()
    {
        $channel = 'channel';
        $receiverEntityFQCN = 'FQCN';
        $receiverEntityId = 2;
        $topic = 'topic';

        $this->sender->expects($this->once())
            ->method('remove')
            ->with($channel, $topic, $receiverEntityFQCN, $receiverEntityId);

        $event = new MassMessagesEvent($channel, $receiverEntityFQCN, $receiverEntityId, $topic);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(MessageEvents::ON_REMOVE, $event);

        $this->messenger->remove($channel, $topic, $receiverEntityFQCN, $receiverEntityId);
    }

    public function testRemove()
    {
        $channel = 'channel';
        $receiverEntityFQCN = 'FQCN';
        $receiverEntityId = 2;
        $topic = 'topic';

        $this->sender->expects($this->once())
            ->method('remove')
            ->with($channel, $topic, $receiverEntityFQCN, $receiverEntityId);

        /** @var TransportInterface|\PHPUnit_Framework_MockObject_MockObject $transport * */
        $transport = $this->getMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('remove')
            ->with($channel, $topic, $receiverEntityFQCN, $receiverEntityId);

        $this->messenger->addTransport($transport);

        $event = new MassMessagesEvent($channel, $receiverEntityFQCN, $receiverEntityId, $topic);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(MessageEvents::ON_REMOVE, $event);

        $this->messenger->remove($channel, $topic, $receiverEntityFQCN, $receiverEntityId);
    }
}
