<?php

namespace OroB2B\Component\Duplicator\Matcher;

use DeepCopy\Matcher\Matcher;

use OroB2B\Component\Duplicator\AbstractFactory;
use OroB2B\Component\Duplicator\ObjectType;

/**
 * @method Matcher create(ObjectType $objectType)
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
