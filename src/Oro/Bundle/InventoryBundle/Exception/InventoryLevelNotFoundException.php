<?php

namespace Oro\Bundle\InventoryBundle\Exception;

class InventoryLevelNotFoundException extends \Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (empty($message)) {
            $message = 'No InventoryLevel found for Product and ProductUnit';
        }
        parent::__construct($message, $code, $previous);
    }
}
