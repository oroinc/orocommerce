<?php

namespace Oro\Bundle\TaxBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ContextEvent extends Event
{
    const NAME = 'orob2b_tax.mapper.context';

    /**
     * @var object
     */
    protected $mappingObject;

    /**
     * @var \ArrayObject
     */
    protected $context;

    /**
     * @param object $mappingObject
     */
    public function __construct($mappingObject)
    {
        $this->mappingObject = $mappingObject;
        $this->context = new \ArrayObject();
    }

    /**
     * @return object
     */
    public function getMappingObject()
    {
        return $this->mappingObject;
    }

    /**
     * @return \ArrayObject
     */
    public function getContext()
    {
        return $this->context;
    }
}
