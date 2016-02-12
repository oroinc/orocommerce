<?php

namespace OroB2B\Bundle\TaxBundle\Model;

abstract class AbstractResultElement extends AbstractResult
{
    /**
     * @param string $index
     * @param string $value
     */
    public function offsetSet($index, $value)
    {
        parent::offsetSet((string)$index, (string)$value);
    }
}
