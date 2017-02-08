<?php

namespace Oro\Bundle\InventoryBundle\Exception;

class InsufficientInventoryQuantityException extends \Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'Insufficient quantity remaining in inventory!';
        }
        parent::__construct($message, $code, $previous);
    }
}
