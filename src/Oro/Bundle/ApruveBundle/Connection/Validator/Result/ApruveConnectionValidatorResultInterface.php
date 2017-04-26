<?php

namespace Oro\Bundle\ApruveBundle\Connection\Validator\Result;

interface ApruveConnectionValidatorResultInterface
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
