<?php

namespace Oro\Bundle\PromotionBundle\Layout\DataProvider\DTO;

use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

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
        if (!$object instanceof ProductLineItemInterface) {
            throw new UnsupportedObjectException(
                sprintf(
                    'Only instances of %s are supported. %s given',
                    ProductLineItemInterface::class,
                    get_class($object)
                )
            );
        }

        // If given entity has not empty id - use it as offset to improve performance
        if (method_exists($object, 'getId') && $object->getId()) {
            return $object->getId();
        }

        $identifier = [
            $object->getProductSku(),
            $object->getProductUnitCode(),
            $object->getQuantity()
        ];
        return implode(':', $identifier);
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
