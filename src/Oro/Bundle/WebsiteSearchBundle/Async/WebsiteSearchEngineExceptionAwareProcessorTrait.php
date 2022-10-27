<?php

namespace Oro\Bundle\WebsiteSearchBundle\Async;

use Monolog\Logger;
use Oro\Bundle\WebsiteSearchBundle\Event\SearchProcessingEngineExceptionEvent;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Allows to wrap the operation on a search index with a try-catch block that can detect a retryable exception
 * to return requeue message status.
 */
trait WebsiteSearchEngineExceptionAwareProcessorTrait
{
    /**
     * @param callable $processCallback The callback that makes some operation on a search index.
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface|null $logger
     *
     * @return string Message status
     */
    private function doProcess(
        callable $processCallback,
        EventDispatcherInterface $eventDispatcher,
        ?LoggerInterface $logger = null
    ): string {
        try {
            return $processCallback();
        } catch (\Exception $exception) {
            $event = new SearchProcessingEngineExceptionEvent($exception);
            $eventDispatcher->dispatch($event, SearchProcessingEngineExceptionEvent::EVENT_NAME);

            $logger?->log(
                $event->isRetryable() ? Logger::WARNING : Logger::ERROR,
                'An unexpected exception occurred while working with search index. Error: {message}',
                [
                    'exception' => $exception,
                    'message' => $exception->getMessage(),
                ]
            );

            return $event->isRetryable() ? MessageProcessorInterface::REQUEUE : MessageProcessorInterface::REJECT;
        }
    }
}
