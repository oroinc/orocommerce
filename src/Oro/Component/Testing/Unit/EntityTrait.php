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

        if ($constructorArgs) {
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
     * @return PropertyAccessor
     */
    public function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }

    /**
     * @param string $className
     * @param int $id
     *
     * @return object
     *
     * @deprecated Use createEntity instead
     */
    protected function createEntity($className, $id)
    {
        return $this->getEntity($className, ['id' => $id]);
    }
}
