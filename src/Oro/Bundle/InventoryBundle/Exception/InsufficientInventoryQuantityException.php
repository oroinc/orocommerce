<?php

namespace Oro\Bundle\InventoryBundle\Exception;

/**
 * Thrown when an operation cannot be completed due to insufficient inventory quantity.
 *
 * Indicates that the requested quantity exceeds the available inventory,
 * preventing the operation from proceeding.
 */
class InsufficientInventoryQuantityException extends \Exception
{
    public function __construct($message = "", $code = 0, ?\Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'Insufficient quantity remaining in inventory!';
        }
        parent::__construct($message, $code, $previous);
    }
}
