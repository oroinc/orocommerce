<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\ProductBundle\Model\ProductMessageHandler;
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
        $this->getMessageCollector()->enable();
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

        return array_filter(
            $this->getMessageProducer()->getTraces(),
            function (array $trace) {
                return $this->topic === $trace['topic'];
            }
        );
    }
//    protected function sendScheduledMessages()
//    {
//        self::getContainer()->get('oro_customer.visibility_message_handler')
//            ->sendScheduledMessages();
//    }

    /**
     * @return TraceableMessageProducer
     */
    protected function getMessageProducer()
    {
        return self::getContainer()->get('oro_message_queue.message_producer');
    }
}
