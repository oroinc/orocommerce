<?php

namespace Oro\Bundle\ApplicationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ModelIdentifierEvent extends Event
{
    /**
     * @var int|string
     */
    protected $identifier;

    /**
     * @param int|string $identifier
     */
    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }

    /**
     * @return int|string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param int|string $identifier
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
    }
}
