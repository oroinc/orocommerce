<?php

namespace OroB2B\Component\Duplicator\Filter;

use DeepCopy\Filter\Filter;

use OroB2B\Component\Duplicator\AbstractFactory;
use OroB2B\Component\Duplicator\ObjectType;

/**
 * @method Filter create(ObjectType $objectType)
 */
class FilterFactory extends AbstractFactory
{
    /**
     * @return string
     */
    protected function getSupportedClassName()
    {
        return '\DeepCopy\Filter\Filter';
    }
}
