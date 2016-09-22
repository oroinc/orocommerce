<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

/**
 * This class is DTO for passing value with placeholders needed to replace
 */
class ValueWithPlaceholders
{
    /** @var array */
    private $placeholders;

    /** @var mixed */
    private $value;

    /**
     * @param $value
     * @param array $placeholders
     */
    public function __construct($value, array $placeholders)
    {
        $this->placeholders = $placeholders;
        $this->value = $value;
    }

    /**
     * @return array
     */
    public function getPlaceholders()
    {
        return $this->placeholders;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
