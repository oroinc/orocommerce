<?php

namespace Oro\Bundle\TaxBundle\Exception;

use Exception;

/**
 * Thrown when tax calculation is attempted while taxation is disabled in system configuration.
 *
 * This exception is used to signal that tax-related operations cannot be performed because
 * the taxation feature has been disabled. Callers should catch this exception and handle the disabled state
 * appropriately, typically by skipping tax calculations or displaying appropriate messages to users.
 */
class TaxationDisabledException extends \Exception
{
    public function __construct($message = 'Taxation disabled', $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
