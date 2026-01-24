<?php

namespace Oro\Bundle\InventoryBundle\Exception;

/**
 * Thrown when an invalid quantity value is provided for an inventory level.
 *
 * Indicates that the provided quantity value does not meet the requirements
 * for inventory level operations.
 */
class InvalidInventoryLevelQuantityException extends \Exception
{
    public function __construct($message = "", $code = 0, ?\Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'Invalid quantity value provided!';
        }
        parent::__construct($message, $code, $previous);
    }
}
