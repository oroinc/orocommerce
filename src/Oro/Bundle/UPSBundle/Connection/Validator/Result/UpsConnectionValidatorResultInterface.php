<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Result;

interface UpsConnectionValidatorResultInterface
{
    /**
     * @return bool
     */
    public function getStatus();

    /**
     * @return string|null
     */
    public function getErrorSeverity();

    /**
     * @return string|null
     */
    public function getErrorMessage();
}
