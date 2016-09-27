<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\NotificationMessage\Transport;

use Doctrine\Common\Persistence\ManagerRegistry;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\PricingBundle\Entity\NotificationMessage;
use Oro\Bundle\PricingBundle\Entity\Repository\NotificationMessageRepository;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;
use Oro\Bundle\PricingBundle\NotificationMessage\Transport\DatabaseTransport;

class DatabaseTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var DatabaseTransport
     */
    protected $databaseTransport;

    protected function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->databaseTransport = new DatabaseTransport($this->registry);
    }

    public function testSend()
    {
        $message = new Message(
            'channel',
            'topic',
            'message',
            'status',
            'EntityFQCN',
            42
        );
        $messageEntity = new NotificationMessage();
        $messageEntity->setChannel('channel');
        $messageEntity->setTopic('topic');
        $messageEntity->setMessage('message');
        $messageEntity->setMessageStatus('status');
        $messageEntity->setReceiverEntityFQCN('EntityFQCN');
        $messageEntity->setReceiverEntityId(42);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('persist')
            ->with($messageEntity);
        $em->expects($this->once())
            ->method('flush')
            ->with($messageEntity);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(NotificationMessage::class)
            ->willReturn($em);

        $this->databaseTransport->send($message);
    }

    public function testRemove()
    {
        $channel = 'channel';
        $topic = 'topic';
        $receiverEntityFQCN = 'EntityFQCN';
        $receiverEntityId = 42;

        $repo = $this->getMockBuilder(NotificationMessageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('removeMessages')
            ->with($channel, $topic, $receiverEntityFQCN, $receiverEntityId);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(NotificationMessage::class)
            ->willReturn($repo);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(NotificationMessage::class)
            ->willReturn($em);

        $this->databaseTransport->remove($channel, $topic, $receiverEntityFQCN, $receiverEntityId);
    }

    public function testReceive()
    {
        $channel = 'channel';
        $topic = 'topic';
        $receiverEntityFQCN = 'EntityFQCN';
        $receiverEntityId = 42;

        $messageEntity1 = new NotificationMessage();
        $messageEntity1->setChannel('channel');
        $messageEntity1->setTopic('topic');
        $messageEntity1->setMessage('message');
        $messageEntity1->setMessageStatus('status');
        $messageEntity1->setReceiverEntityFQCN('EntityFQCN');
        $messageEntity1->setReceiverEntityId(42);

        $messageEntity2 = new NotificationMessage();
        $messageEntity2->setChannel('channel2');
        $messageEntity2->setTopic('topic2');
        $messageEntity2->setMessage('message2');
        $messageEntity2->setMessageStatus('status2');

        $messages = [$messageEntity1, $messageEntity2];

        $repo = $this->getMockBuilder(NotificationMessageRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('receiveMessages')
            ->with($channel, $receiverEntityFQCN, $receiverEntityId, $topic)
            ->willReturn($messages);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(NotificationMessage::class)
            ->willReturn($repo);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(NotificationMessage::class)
            ->willReturn($em);

        $actualMessages = [];
        foreach ($this->databaseTransport->receive($channel, $receiverEntityFQCN, $receiverEntityId, $topic) as $msg) {
            $actualMessages[] = $msg;
        }

        $expectedMessages = [
            new Message('channel', 'topic', 'message', 'status', 'EntityFQCN', 42),
            new Message('channel2', 'topic2', 'message2', 'status2')
        ];
        $this->assertEquals($expectedMessages, $actualMessages);
    }
}
