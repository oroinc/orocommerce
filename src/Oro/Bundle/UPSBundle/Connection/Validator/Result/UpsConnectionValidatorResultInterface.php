<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Result;

/**
 * Interface for UPS Connection Validator Result
 */
interface UpsConnectionValidatorResultInterface
{
    public function getStatus(): bool;

    public function getErrorSeverity(): ?string;

    public function getErrorMessage(): ?string;
}
