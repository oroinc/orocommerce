<?php

namespace OroB2B\Bundle\TaxBundle\Model;

abstract class AbstractResultElement extends AbstractResult
{
    /**
     * @param mixed $index
     * @param mixed $value
     */
    public function offsetSet($index, $value)
    {
        parent::offsetSet((string)$index, (string)$value);
    }
}
