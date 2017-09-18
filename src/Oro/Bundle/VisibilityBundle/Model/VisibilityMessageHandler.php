<?php

namespace Oro\Bundle\VisibilityBundle\Model;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class VisibilityMessageHandler
{
    /**
     * @var VisibilityMessageFactory
     */
    protected $messageFactory;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var array
     */
    protected $scheduledMessages = [];

    /**
     * @param MessageFactoryInterface  $messageFactory
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        MessageFactoryInterface $messageFactory,
        MessageProducerInterface $messageProducer
    ) {
        $this->messageFactory = $messageFactory;
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param string $topic
     * @param object $entity
     */
    public function addMessageToSchedule($topic, $entity)
    {
        if (!$this->isScheduledMessage($topic, $entity)) {
            $this->scheduleMessage($topic, $entity);
        }
    }

    public function sendScheduledMessages()
    {
        foreach ($this->scheduledMessages as $topic => $entities) {
            if (count($entities) > 0) {
                foreach ($entities as $entity) {
                    $message = $this->messageFactory->createMessage($entity);
                    $this->messageProducer->send($topic, $message);
                }
            }
        }

        $this->scheduledMessages = [];
    }

    /**
     * @param string $topic
     * @param object $entity
     *
     * @return bool
     */
    protected function isScheduledMessage($topic, $entity)
    {
        if (empty($this->scheduledMessages[$topic][$this->getMessageKey($entity)])) {
            return false;
        }

        return true;
    }

    /**
     * @param string $topic
     * @param object $entity
     */
    protected function scheduleMessage($topic, $entity)
    {
        $this->scheduledMessages[$topic][$this->getMessageKey($entity)] = $entity;
    }

    /**
     * @param object $entity
     *
     * @return string
     */
    protected function getMessageKey($entity)
    {
        return spl_object_hash($entity);
    }
}
