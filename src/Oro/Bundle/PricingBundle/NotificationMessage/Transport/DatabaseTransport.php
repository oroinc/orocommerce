<?php

namespace Oro\Bundle\PricingBundle\NotificationMessage\Transport;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\PricingBundle\Entity\NotificationMessage;
use Oro\Bundle\PricingBundle\Entity\Repository\NotificationMessageRepository;
use Oro\Bundle\PricingBundle\NotificationMessage\Message;

class DatabaseTransport implements TransportInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Message $message)
    {
        $messageEntity = new NotificationMessage();
        $messageEntity->setChannel($message->getChannel());
        $messageEntity->setTopic($message->getTopic());
        $messageEntity->setMessage($message->getMessage());
        $messageEntity->setMessageStatus($message->getStatus());
        $messageEntity->setReceiverEntityFQCN($message->getReceiverEntityFQCN());
        $messageEntity->setReceiverEntityId($message->getReceiverEntityId());

        $em = $this->getEntityManager();
        $em->persist($messageEntity);
        $em->flush($messageEntity);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($channel, $topic = null, $receiverEntityFQCN = null, $receiverEntityId = null)
    {
        $this->getRepository()->removeMessages($channel, $topic, $receiverEntityFQCN, $receiverEntityId);
    }

    /**
     * {@inheritdoc}
     */
    public function receive($channel, $receiverEntityFQCN = null, $receiverEntityId = null, $topic = null)
    {
        $messages = $this->getRepository()->receiveMessages($channel, $receiverEntityFQCN, $receiverEntityId);
        foreach ($messages as $messageEntity) {
            yield new Message(
                $messageEntity->getChannel(),
                $messageEntity->getTopic(),
                $messageEntity->getMessage(),
                $messageEntity->getMessageStatus(),
                $messageEntity->getReceiverEntityFQCN(),
                $messageEntity->getReceiverEntityId()
            );
        }
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass(NotificationMessage::class);
    }

    /**
     * @return NotificationMessageRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository(NotificationMessage::class);
    }
}
