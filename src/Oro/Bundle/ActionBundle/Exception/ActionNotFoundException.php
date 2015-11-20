<?php

namespace Oro\Bundle\ActionBundle\Exception;

class ActionNotFoundException extends \Exception
{
    public function __construct($name)
    {
        parent::__construct(sprintf('Action with name "%s" not found', $name));
    }
}
