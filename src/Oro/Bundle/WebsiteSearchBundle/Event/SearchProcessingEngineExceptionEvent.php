<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

/**
 * Search processing exception.
 */
class SearchProcessingEngineExceptionEvent
{
    const EVENT_NAME = 'oro_website_search.processing_engine_exception';

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var null|string
     */
    private $consumptionResult;

    public function __construct(\Exception $exception, ?string $consumptionResult = null)
    {
        $this->exception = $exception;
        $this->consumptionResult = $consumptionResult;
    }

    public function getException(): \Exception
    {
        return $this->exception;
    }

    public function getConsumptionResult(): ?string
    {
        return $this->consumptionResult;
    }

    public function setConsumptionResult(?string $consumptionResult): void
    {
        $this->consumptionResult = $consumptionResult;
    }
}
