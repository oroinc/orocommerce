<?php

namespace Oro\Bundle\ProductBundle\Model;

/**
 * Represents a field in the quick add form with its name and value.
 *
 * This value object encapsulates a single field's data for the quick add product functionality,
 * storing both the field identifier and its associated value. It provides a simple, immutable structure
 * for passing field data through the quick add workflow.
 */
class QuickAddField
{
    /** @var string */
    private $name;

    /** @var mixed */
    private $value;

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
