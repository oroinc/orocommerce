<?php

namespace Oro\Bundle\TaxBundle\Model;

abstract class AbstractResult extends \ArrayObject
{
    /**
     * @param string $offset
     * @param mixed $default
     * @return mixed
     */
    public function getOffset($offset, $default = null)
    {
        if ($this->offsetExists((string)$offset)) {
            return $this->offsetGet((string)$offset);
        }

        return $default;
    }

    /**
     * @param string $offset
     */
    public function unsetOffset($offset)
    {
        if ($this->offsetExists((string)$offset)) {
            $this->offsetUnset((string)$offset);
        }
    }
}
