<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\NotificationMessage;

use Oro\Bundle\PricingBundle\NotificationMessage\Event\MassMessagesEvent;
use Oro\Bundle\PricingBundle\NotificationMessage\Event\MessageEvent;
use Oro\Bundle\PricingBundle\NotificationMessage\Event\MessageEvents;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\PricingBundle\NotificationMessage\Messenger;
use Oro\Bundle\PricingBundle\NotificationMessage\Transport\TransportInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MessengerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TransportInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $sender;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    /**
     * @var Messenger
     */
    protected $messenger;

    protected function setUp(): void
    {
        $this->sender = $this->createMock(TransportInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->messenger = new Messenger($this->sender, $this->eventDispatcher);
    }

    public function testAddTransport()
    {
        $messenger = new Messenger(
            $this->createMock(TransportInterface::class),
            $this->createMock(EventDispatcherInterface::class)
        );
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->exactly(2)) // 1 on the first remove(), and only 1 on the second remove
            ->method('remove');
        $messenger->addTransport($transport);
        $messenger->remove('channel', 'topic');

        // Check that even after we attempt to add the same transport twice it would be not called twice anyway
        $this->messenger->addTransport($transport);
        $messenger->remove('channel', 'topic');
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
                [$messageEvent, MessageEvents::BEFORE_SEND],
                [$messageEvent, MessageEvents::AFTER_SEND]
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
            ->with($event, MessageEvents::ON_RECEIVE);

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

        /** @var TransportInterface|\PHPUnit\Framework\MockObject\MockObject $transport * */
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('receive')
            ->with($channel, $receiverEntityFQCN, $receiverEntityId, $topic)
            ->willReturn([$message2]);

        $this->messenger->addTransport($transport);

        $messages = [$message1, $message2];
        $event = new MassMessagesEvent($channel, $receiverEntityFQCN, $receiverEntityId, $topic, $messages);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, MessageEvents::ON_RECEIVE);

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
            ->with($event, MessageEvents::ON_REMOVE);

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

        /** @var TransportInterface|\PHPUnit\Framework\MockObject\MockObject $transport * */
        $transport = $this->createMock(TransportInterface::class);
        $transport->expects($this->once())
            ->method('remove')
            ->with($channel, $topic, $receiverEntityFQCN, $receiverEntityId);

        $this->messenger->addTransport($transport);

        $event = new MassMessagesEvent($channel, $receiverEntityFQCN, $receiverEntityId, $topic);
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, MessageEvents::ON_REMOVE);

        $this->messenger->remove($channel, $topic, $receiverEntityFQCN, $receiverEntityId);
    }
}
