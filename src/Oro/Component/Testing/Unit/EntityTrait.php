<?php

namespace Oro\Component\Testing\Unit;

use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

trait EntityTrait
{
    /**
     * @var PropertyAccessor
     */
    private $propertyAccessor;

    /**
     * @param string $className
     * @param array $properties Like ['id' => 1]
     * @param array $constructorArgs Like ['id' => 1]
     *
     * @return object
     */
    protected function getEntity($className, array $properties = [], array $constructorArgs = null)
    {
        $reflectionClass = new \ReflectionClass($className);

        if ($reflectionClass->hasMethod('__construct')) {
            $entity = $reflectionClass->newInstance($constructorArgs);
        } else {
            $entity = $reflectionClass->newInstanceWithoutConstructor();
        }

        foreach ($properties as $property => $value) {
            try {
                $this->getPropertyAccessor()->setValue($entity, $property, $value);
            } catch (NoSuchPropertyException $e) {
                $method = $reflectionClass->getProperty($property);
                $method->setAccessible(true);
                $method->setValue($entity, $value);
            } catch (\ReflectionException $e) {
            }
        }

        return $entity;
    }

    /**
     * @param sring $ident unique ident of an entity
     * @param string $className
     * @param array $properties Like ['id' => 1]
     * @param array $constructorArgs Like ['id' => 1]
     *
     * @return object
     */
    protected function getUniqueEntity($ident, $className, array $properties = [], array $constructorArgs = null)
    {
        static $entities = [];

        if (!isset($entities[$className][$ident])) {
            $entities[$className][$ident] = $this->getEntity($className, $properties, $constructorArgs);
        }

        return $entities[$className][$ident];
    }

    /**
     * @return PropertyAccessor
     */
    public function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
