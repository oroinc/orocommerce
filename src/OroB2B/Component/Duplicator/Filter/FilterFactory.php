<?php

namespace OroB2B\Component\Duplicator\Filter;

use DeepCopy\Filter\Filter as BaseFilter;

use OroB2B\Component\Duplicator\AbstractFactory;
use OroB2B\Component\Duplicator\ObjectType;

/**
 * @method BaseFilter create(ObjectType $objectType)
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
