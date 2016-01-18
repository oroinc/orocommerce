<?php

namespace OroB2B\Bundle\TaxBundle\Model;

abstract class AbstractResult extends \ArrayObject
{
    /**
     * @param string $offset
     * @param mixed $default
     * @return mixed
     */
    protected function getOffset($offset, $default = null)
    {
        if ($this->offsetExists((string)$offset)) {
            return $this->offsetGet((string)$offset);
        }

        return $default;
    }
}
