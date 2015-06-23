<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Entity;

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

    /**
     * @param object $object
     * @param object $item
     */
    protected function assertCollection($object, $item)
    {
        $ritem = new \ReflectionClass($item);

        $class = $ritem->getShortName();

        $addMethod      = 'add' . $class;
        $removeMethod   = 'remove' . $class;
        $getMethod      = 'get' . $class . 's';

        $this->assertCount(0, $object->$getMethod());

        // Add new item
        $this->assertSame($object, $object->$addMethod($item));
        $this->assertCount(1, $object->$getMethod());

        $actual = $object->$getMethod();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertEquals([$item], $actual->toArray());

        // Add already added item
        $this->assertSame($object, $object->$addMethod($item));
        $this->assertCount(1, $object->$getMethod());

        // Remove item
        $this->assertSame($object, $object->$removeMethod($item));
        $this->assertCount(0, $object->$getMethod());

        // Remove already removed item
        $this->assertSame($object, $object->$removeMethod($item));
        $this->assertCount(0, $object->$getMethod());

        $actual = $object->$getMethod();
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $actual);
        $this->assertNotContains($item, $actual->toArray());
    }
}
