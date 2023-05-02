<?php

namespace Oro\Bundle\PricingBundle\Exception;

/**
 * Exception for failed ProductPriceCriteria building
 */
class ProductPriceCriteriaBuildingFailedException extends \InvalidArgumentException
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'One of parameters for building ProductPriceCriteria is invalid! See details in logs.';
        }
        parent::__construct($message, $code, $previous);
    }
}
