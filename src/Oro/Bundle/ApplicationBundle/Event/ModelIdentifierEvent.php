<?php

namespace Oro\Bundle\ApplicationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ModelIdentifierEvent extends Event
{
    /**
     * @var mixed
     */
    protected $identifier;

    /**
     * @param mixed $identifier
     */
    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }

    /**
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param mixed $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }
}
