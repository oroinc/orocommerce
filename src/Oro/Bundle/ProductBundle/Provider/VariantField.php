<?php

namespace Oro\Bundle\ProductBundle\Provider;

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
