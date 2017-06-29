<?php

namespace Oro\Bundle\PromotionBundle\Layout\DataProvider\DTO;

/**
 * The ObjectStorage class provides a map from objects to data.
 */
class ObjectStorage implements \Countable
{
    /**
     * @var array
     */
    private $storage = [];

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->storage);
    }

    /**
     * @param object $object
     * @return string
     */
    private function getOffset($object): string
    {
        // If given entity has not empty id - use it as offset to improve performance
        if (method_exists($object, 'getId') && $object->getId()) {
            return $object->getId();
        }

        return md5(serialize($object));
    }

    /**
     * @param object $object
     * @return bool
     */
    public function contains($object): bool
    {
        return array_key_exists($this->getOffset($object), $this->storage);
    }

    /**
     * @param object $object
     * @return mixed
     */
    public function get($object)
    {
        return $this->storage[$this->getOffset($object)];
    }

    /**
     * @param object $object
     * @param mixed $value
     */
    public function attach($object, $value)
    {
        $this->storage[$this->getOffset($object)] = $value;
    }

    /**
     * @param object $object
     */
    public function detach($object)
    {
        unset($this->storage[$this->getOffset($object)]);
    }
}
