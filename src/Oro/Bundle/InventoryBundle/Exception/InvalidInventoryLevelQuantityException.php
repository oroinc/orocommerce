<?php

namespace Oro\Bundle\InventoryBundle\Exception;

class InvalidInventoryLevelQuantityException extends \Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'Invalid quantity value provided!';
        }
        parent::__construct($message, $code, $previous);
    }
}
