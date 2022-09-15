<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexGranulizedTopic;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Makes reindex of the specified entities by class and ids.
 */
class WebsiteSearchReindexGranulizedProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;
    use WebsiteSearchEngineExceptionAwareProcessorTrait;

    private IndexerInterface $indexer;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(IndexerInterface $indexer, EventDispatcherInterface $eventDispatcher)
    {
        $this->indexer = $indexer;
        $this->eventDispatcher = $eventDispatcher;

        $this->logger = new NullLogger();
    }

    public static function getSubscribedTopics(): array
    {
        return [WebsiteSearchReindexGranulizedTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        return $this->doReindex($messageBody['class'], $messageBody['context']);
    }

    public function doReindex(array|string $class, array $context): string
    {
        return $this->doProcess(
            function () use ($class, $context) {
                $this->indexer->reindex($class, array_merge($context, ['skip_pre_processing' => true]));

                return self::ACK;
            },
            $this->eventDispatcher,
            $this->logger
        );
    }
}
