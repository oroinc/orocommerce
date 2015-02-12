<?php

namespace Oro\Component\Testing\Unit;

use Oro\Component\Testing\Unit\Constraint\IsTimestampable;
use Oro\Component\Testing\Unit\Constraint\PropertyGetterReturnsDefaultValue;
use Oro\Component\Testing\Unit\Constraint\PropertyGetterReturnsSetValue;

abstract class EntityTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @param object $instance
     * @param string $propertyName
     * @param string $message
     */
    public static function assertPropertyGetterReturnsDefaultValue($instance, $propertyName, $message = '')
    {
        self::assertThat($instance, self::propertyGetterReturnsDefaultValue($propertyName), $message);
    }

    /**
     * Returns a \Oro\Component\Testing\Unit\Constraint\PropertyGetterReturnsDefaultValue matcher object.
     *
     * @param string $propertyName
     * @return \Oro\Component\Testing\Unit\Constraint\PropertyGetterReturnsDefaultValue
     */
    public static function propertyGetterReturnsDefaultValue($propertyName)
    {
        return new PropertyGetterReturnsDefaultValue(
            $propertyName
        );
    }

    /**
     * @param object $instance
     * @param string $propertyName
     * @param mixed $testValue
     * @param string $message
     */
    public static function assertPropertyGetterReturnsSetValue($instance, $propertyName, $testValue, $message = '')
    {
        self::assertThat($instance, self::propertyGetterReturnsSetValue($propertyName, $testValue), $message);
    }

    /**
     * Returns a \Oro\Component\Testing\Unit\Constraint\PropertyGetterReturnsSetValue matcher object.
     *
     * @param string $propertyName
     * @param mixed $testValue
     * @return \Oro\Component\Testing\Unit\Constraint\PropertyGetterReturnsSetValue
     */
    public static function propertyGetterReturnsSetValue($propertyName, $testValue)
    {
        return new PropertyGetterReturnsSetValue(
            $propertyName,
            $testValue
        );
    }

    /**
     * Performance assertPropertyGetterReturnsDefaultValue and assertPropertyGetterReturnsSetValue assertions
     * on specified properties.
     *
     * @param object $instance
     * @param array $properties <p>
     * Example:
     * [1] => Array(
     *   [1] => 'someProperty', // property name
     *   [2] => 123.45,         // a value it can be tested with
     *   [3] => true            // property has some default value
     * )
     * [2] => Array(
     *   [1] => 'anotherProperty',
     *   [2] => SomeComplexObject(...),
     *   [3] => false   // do not test default value (e.g. if the property is initialized in the constructor,
     *                  // or is lazy loaded by its getter)
     * )
     * </p>
     */
    public static function assertPropertyAccessors($instance, $properties)
    {
        foreach ($properties as $property) {
            $testInstance = clone $instance;

            $propertyName = $property[0];
            $testValue = $property[1];
            $testDefaultValue = isset($property[2]) ? $property[2] : true;

            if ($testDefaultValue) {
                self::assertPropertyGetterReturnsDefaultValue($testInstance, $propertyName);
            }
            self::assertPropertyGetterReturnsSetValue($testInstance, $propertyName, $testValue);
        }
    }
}
