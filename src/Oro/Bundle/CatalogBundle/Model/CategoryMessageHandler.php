<?php

namespace Oro\Bundle\CatalogBundle\Model;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class CategoryMessageHandler
{
    /**
     * @var CategoryMessageFactory
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
     * @param CategoryMessageFactory $messageFactory
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        CategoryMessageFactory $messageFactory,
        MessageProducerInterface $messageProducer
    ) {
        $this->messageFactory = $messageFactory;
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param string $topic
     * @param Category|null $category
     */
    public function addCategoryMessageToSchedule($topic, Category $category = null)
    {
        $message = $this->messageFactory->createMessage($category);

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
        return $message[CategoryMessageFactory::ID];
    }
}
