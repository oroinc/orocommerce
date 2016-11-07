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
     * @param MessageFactoryInterface $messageFactory
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
        $message = $this->messageFactory->createMessage($entity);

        if (!$this->isScheduledMessage($topic, $message)) {
            $this->scheduleMessage($topic, $message);
        }
    }

    public function sendScheduledMessages()
    {
        foreach ($this->scheduledMessages as $topic => $messages) {
            if (count($messages) > 0) {
                foreach ($messages as $message) {
                    $this->messageProducer->send($topic, $message);
                }
            }
        }

        $this->scheduledMessages = [];
    }

    /**
     * @param string $topic
     * @param array $message
     * @return bool
     */
    protected function isScheduledMessage($topic, array $message)
    {
        $messages = empty($this->scheduledMessages[$topic]) ? [] : $this->scheduledMessages[$topic];

        return array_key_exists($this->getMessageKey($message), $messages);
    }

    /**
     * @param string $topic
     * @param array $message
     */
    protected function scheduleMessage($topic, array $message)
    {
        $this->scheduledMessages[$topic][$this->getMessageKey($message)] = $message;
    }

    /**
     * @param $message
     * @return string
     */
    protected function getMessageKey($message)
    {
        return $message[VisibilityMessageFactory::ENTITY_CLASS_NAME] . ':' . $message[VisibilityMessageFactory::ID];
    }
}
