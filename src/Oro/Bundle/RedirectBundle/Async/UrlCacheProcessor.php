<?php

namespace Oro\Bundle\RedirectBundle\Async;

use Oro\Bundle\RedirectBundle\Async\Topic\CalculateSlugCacheTopic;
use Oro\Bundle\RedirectBundle\Cache\Dumper\SluggableUrlDumper;
use Oro\Bundle\RedirectBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Fill Slug URL caches with data received for a given set of entities.
 */
class UrlCacheProcessor implements MessageProcessorInterface, TopicSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private MessageFactoryInterface $messageFactory;

    private SluggableUrlDumper $dumper;

    public function __construct(MessageFactoryInterface $messageFactory, SluggableUrlDumper $dumper)
    {
        $this->messageFactory = $messageFactory;
        $this->dumper = $dumper;

        $this->logger = new NullLogger();
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageData = $message->getBody();
        $entities = $this->messageFactory->getEntitiesFromMessage($messageData);
        foreach ($entities as $entity) {
            $this->dumper->dump($entity);
        }

        return self::ACK;
    }

    public static function getSubscribedTopics(): array
    {
        return [CalculateSlugCacheTopic::getName()];
    }
}
