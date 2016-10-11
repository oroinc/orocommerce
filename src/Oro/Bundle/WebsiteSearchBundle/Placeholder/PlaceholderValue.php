<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

/**
 * This class is DTO for passing value with placeholders needed to replace
 */
class PlaceholderValue
{
    /** @var string */
    private $value;

    /** @var array */
    private $placeholders = [];

    /**
     * @param string $value
     * @param array $placeholders
     */
    public function __construct($value, array $placeholders = [])
    {
        $this->value = (string)$value;
        $this->placeholders = $placeholders;
    }

    /**
     * @return array
     */
    public function getPlaceholders()
    {
        return $this->placeholders;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }
}
