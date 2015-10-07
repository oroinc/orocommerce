<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Fixtures;

class TestEntity
{
    /**
     * @param string $toStringValue
     */
    public function __construct($toStringValue = '')
    {
        $this->toStringValue = $toStringValue;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toStringValue;
    }
}
