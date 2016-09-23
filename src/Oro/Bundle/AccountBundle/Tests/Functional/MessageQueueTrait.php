<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional;

use Oro\Bundle\VisibilityBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\ProductBundle\Model\ProductMessageHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method static ContainerInterface getContainer
 */
trait MessageQueueTrait
{
    /**
     * @var string
     */
    protected $topic;

    protected function cleanQueueMessageTraces()
    {
        $this->sendScheduledMessages();

        $this->getMessageProducer()->enable();
        $this->getMessageProducer()->clear();
    }

    /**
     * @return array
     */
    protected function getQueueMessageTraces()
    {
        $this->sendScheduledMessages();

        return array_filter(
            $this->getMessageProducer()->getSentMessages(),
            function (array $trace) {
                return $this->topic === $trace['topic'];
            }
        );
    }

    /**
     * @return ProductMessageHandler
     */
    abstract function getMessageHandler();

    protected function sendScheduledMessages()
    {
        if ($this->getMessageHandler()) {
            $this->getMessageHandler()->sendScheduledMessages();
        }
    }

    /**
     * @return MessageCollector
     */
    protected function getMessageProducer()
    {
        return self::getContainer()->get('oro_message_queue.message_producer');
    }

    /**
     * @param array $trace
     * @return int
     */
    protected function getEntityIdFromTrace(array $trace)
    {
        return $trace['message'][VisibilityMessageFactory::ID];
    }
    
    /**
     * @param array $trace
     * @return int
     */
    protected function getVisibilityEntityClassFromTrace(array $trace)
    {
        return $trace['message'][VisibilityMessageFactory::ENTITY_CLASS_NAME];
    }
}
