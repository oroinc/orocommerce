<?php

namespace Oro\Bundle\TaxBundle\Exception;

use Exception;

class TaxationDisabledException extends \Exception
{
    public function __construct($message = 'Taxation disabled', $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
