<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional;

use Oro\Bundle\AccountBundle\Model\VisibilityMessageFactory;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
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

    protected function sendScheduledMessages()
    {
        self::getContainer()->get('oro_account.visibility_message_handler')
            ->sendScheduledMessages();
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
