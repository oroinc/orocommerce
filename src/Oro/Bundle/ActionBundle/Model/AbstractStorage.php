<?php

namespace Oro\Bundle\ActionBundle\Model;

abstract class AbstractStorage implements \ArrayAccess, \IteratorAggregate, \Countable
{
    /** @var array */
    protected $data;

    /** @var bool */
    protected $modified = false;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return array_key_exists($offset, $this->data) ? $this->data[$offset] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        if (!array_key_exists($offset, $this->data) || $this->data[$offset] !== $value) {
            $this->data[$offset] = $value;
            $this->modified = true;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        if (array_key_exists($offset, $this->data)) {
            unset($this->data[$offset]);
            $this->modified = true;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * @return bool
     */
    public function isModified()
    {
        return $this->modified;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }
}
