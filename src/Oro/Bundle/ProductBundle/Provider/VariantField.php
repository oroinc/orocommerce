<?php

namespace Oro\Bundle\ProductBundle\Provider;

/**
 * Represents a product variant field with its name and label.
 *
 * This value object encapsulates the information about a variant field, providing both the internal field name
 * and the user-facing label for display in variant selection interfaces.
 */
class VariantField
{
    /** @var string */
    private $name;

    /** @var string */
    private $label;

    public function __construct($name, $label)
    {
        $this->label = $label;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
