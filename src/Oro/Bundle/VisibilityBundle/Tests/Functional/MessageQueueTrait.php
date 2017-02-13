<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\VisibilityBundle\Model\ProductMessageHandler;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @method static ContainerInterface getContainer
 */
trait MessageQueueTrait
{
    use MessageQueueAssertTrait;

    /**
     * @var string
     */
    protected $topic;

    protected function cleanScheduledMessages()
    {
        $this->sendScheduledMessages();
        $this->getMessageCollector()->clear();
    }

    /**
     * @return ProductMessageHandler
     */
    abstract protected function getMessageHandler();

    protected function sendScheduledMessages()
    {
        if ($this->getMessageHandler()) {
            $this->getMessageHandler()->sendScheduledMessages();
        }
    }

    /**
     * @return array
     */
    protected function getQueueMessageTraces()
    {
        $this->sendScheduledMessages();

        /** @var TraceableMessageProducer $producer */
        $producer = self::getMessageProducer();

        return array_filter(
            $producer->getTraces(),
            function (array $trace) {
                return $this->topic === $trace['topic'];
            }
        );
    }
}
