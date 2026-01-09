<?php

namespace Oro\Bundle\TaxBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched to build tax calculation context from domain objects.
 *
 * This event is triggered when the tax system needs to extract tax-related information from domain objects
 * (such as orders, line items, or customers). Event listeners can populate the context with data like tax codes,
 * taxation addresses, digital product flags, and other information required for accurate tax calculations.
 */
class ContextEvent extends Event
{
    public const NAME = 'oro_tax.mapper.context';

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
