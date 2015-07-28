<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCase;

abstract class AbstractTest extends EntityTestCase
{
    /**
     * @param object $object
     * @param string $property
     * @param mixed $value
     * @return AbstractTest
     */
    protected function setProperty($object, $property, $value)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        $reflection->setValue($object, $value);

        return $this;
    }

    /**
     * @param object $object
     * @param string $property
     * @return mixed $value
     */
    protected function getProperty($object, $property)
    {
        $reflection = new \ReflectionProperty(get_class($object), $property);
        $reflection->setAccessible(true);
        return $reflection->getValue($object);
    }
}
