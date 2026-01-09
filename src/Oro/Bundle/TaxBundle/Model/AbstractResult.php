<?php

namespace Oro\Bundle\TaxBundle\Model;

/**
 * Provides common functionality for tax calculation result models.
 *
 * This base class extends ArrayObject to provide array-like access to tax calculation results
 * while adding convenience methods for safe offset access with default values.
 * Subclasses should extend this to create specific result types for different tax calculation scenarios.
 */
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
