<?php

namespace OroB2B\Component\Duplicator\Matcher;

use DeepCopy\Matcher\Matcher;

use OroB2B\Component\Duplicator\AbstractFactory;

/**
 * @method Matcher create(string $keyword, array $constructorArgs)
 */
class MatcherFactory extends AbstractFactory
{
    /**
     * @return string
     */
    protected function getSupportedClassName()
    {
        return '\DeepCopy\Matcher\Matcher';
    }
}
