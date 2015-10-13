<?php

namespace Oro\Component\Testing\Unit;

trait EntityTrait
{
    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function createEntity($className, $id)
    {
        $entity = new $className();

        $reflection = new \ReflectionProperty(get_class($entity), 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($entity, $id);

        return $entity;
    }
}
