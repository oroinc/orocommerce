<?php

namespace OroB2B\Component\Duplicator\Filter;

use DeepCopy\Filter\Filter;

use OroB2B\Component\Duplicator\AbstractFactory;

/**
 * @method Filter create(string $keyword, array $constructorArgs)
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
