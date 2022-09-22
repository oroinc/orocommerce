<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchSaveTopic;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Adds to the website search index the specified entities by class and ids.
 */
class WebsiteSearchSaveProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use WebsiteSearchEngineExceptionAwareProcessorTrait;
    use LoggerAwareTrait;

    private IndexerInterface $indexer;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(IndexerInterface $indexer, EventDispatcherInterface $eventDispatcher)
    {
        $this->indexer = $indexer;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedTopics(): array
    {
        return [WebsiteSearchSaveTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session): string
    {
        $messageBody = $message->getBody();

        return $this->doProcess(
            function () use ($messageBody) {
                $this->indexer->save($messageBody['entity'], $messageBody['context']);

                return self::ACK;
            },
            $this->eventDispatcher,
            $this->logger
        );
    }
}
