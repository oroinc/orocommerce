<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\Entity\EntityListener;

use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
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
        $this->getMessageProducer()->clearTraces();
    }

    /**
     * @return array
     */
    protected function getQueueMessageTraces()
    {
        $this->sendScheduledMessages();

        return array_filter(
            $this->getMessageProducer()->getTraces(),
            function (array $trace) {
                return $this->topic === $trace['topic'];
            }
        );
    }

    protected function sendScheduledMessages()
    {
        self::getContainer()->get('oro_customer.visibility_message_handler')
            ->sendScheduledMessages();
    }

    /**
     * @return TraceableMessageProducer
     */
    protected function getMessageProducer()
    {
        return self::getContainer()->get('oro_message_queue.message_producer');
    }
}
