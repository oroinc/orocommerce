<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Result;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Basic implementation of UPS Connection Validator Result
 */
class UpsConnectionValidatorResult extends ParameterBag implements UpsConnectionValidatorResultInterface
{
    public const STATUS_KEY = 'status';
    public const ERROR_SEVERITY_KEY = 'error_severity';
    public const ERROR_MESSAGE_KEY = 'error_message';

    /**
     * {@inheritDoc}
     */
    public function getStatus(): bool
    {
        return (bool)$this->get(self::STATUS_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getErrorSeverity(): string
    {
        return (string)$this->get(self::ERROR_SEVERITY_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getErrorMessage(): string
    {
        return $this->get(self::ERROR_MESSAGE_KEY);
    }
}
