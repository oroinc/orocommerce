<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Entity;

/**
 * Interface for an entity aware of request and response logs.
 */
interface RequestLogsAwareInterface
{
    public function getRequestLogs(): array;

    public function setRequestLogs(?array $requestLogs = null): self;

    /**
     * Add request log entry to the request logs collection.
     */
    public function addRequestLog(array $requestLog): self;

    public function getResponseLogs(): array;

    public function setResponseLogs(?array $responseLogs = null): self;

    /**
     * Add response log entry to the response logs collection.
     */
    public function addResponseLog(array $responseLog): self;
}
