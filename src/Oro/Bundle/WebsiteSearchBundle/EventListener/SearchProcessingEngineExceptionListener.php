<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\RetryableException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\WebsiteSearchBundle\Event\SearchProcessingEngineExceptionEvent;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * Re-processing message if ORM engine return error.
 */
class SearchProcessingEngineExceptionListener
{
    public function process(SearchProcessingEngineExceptionEvent $event): void
    {
        if ($this->isSupported($event->getException())) {
            $event->setConsumptionResult(MessageProcessorInterface::REQUEUE);
        }
    }

    private function isSupported(\Exception $exception): bool
    {
        return $exception instanceof RetryableException
            || $exception instanceof UniqueConstraintViolationException
            || $exception instanceof ForeignKeyConstraintViolationException;
    }
}
