<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

/**
 * Search processing exception.
 */
class SearchProcessingEngineExceptionEvent
{
    public const EVENT_NAME = 'oro_website_search.processing_engine_exception';

    private \Exception $exception;

    private bool $isRetryable = false;

    public function __construct(\Exception $exception)
    {
        $this->exception = $exception;
    }

    public function getException(): \Exception
    {
        return $this->exception;
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
