<?php

namespace OroB2B\Component\Duplicator\Matcher;

use DeepCopy\Matcher\Matcher as BaseMatcher;

use OroB2B\Component\Duplicator\AbstractFactory;
use OroB2B\Component\Duplicator\ObjectType;

/**
 * @method BaseMatcher create(ObjectType $objectType)
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
