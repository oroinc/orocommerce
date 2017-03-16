<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Result;

use Symfony\Component\HttpFoundation\ParameterBag;

class UpsConnectionValidatorResult extends ParameterBag implements UpsConnectionValidatorResultInterface
{
    const STATUS_KEY = 'status';
    const ERROR_SEVERITY_KEY = 'error_severity';
    const ERROR_MESSAGE_KEY = 'error_message';

    /**
     * {@inheritDoc}
     */
    public function getStatus()
    {
        return (bool)$this->get(self::STATUS_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getErrorSeverity()
    {
        return (string)$this->get(self::ERROR_SEVERITY_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getErrorMessage()
    {
        return $this->get(self::ERROR_MESSAGE_KEY);
    }
}
