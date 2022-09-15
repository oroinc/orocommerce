<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * Search processing exception.
 */
class SearchProcessingEngineExceptionEvent
{
    public const EVENT_NAME = 'oro_website_search.processing_engine_exception';

    private \Exception $exception;

    private bool $isRetryable;

    public function __construct(\Exception $exception, ?string $consumptionResult = null)
    {
        $this->exception = $exception;
        $this->isRetryable = $consumptionResult === MessageProcessorInterface::REQUEUE;
    }

    public function getException(): \Exception
    {
        return $this->exception;
    }

    /**
     * @deprecated Will be removed in 5.1, use ::isRetryable instead.
     */
    public function getConsumptionResult(): ?string
    {
        return $this->isRetryable() ? MessageProcessorInterface::REQUEUE : null;
    }

    /**
     * @deprecated Will be removed in 5.1, use ::setIsRetryable instead.
     */
    public function setConsumptionResult(?string $consumptionResult): void
    {
        $this->setIsRetryable($consumptionResult === MessageProcessorInterface::REQUEUE);
    }

    public function isRetryable(): bool
    {
        return $this->isRetryable;
    }

    public function setIsRetryable(bool $isRetryable): void
    {
        $this->isRetryable = $isRetryable;
    }
}
