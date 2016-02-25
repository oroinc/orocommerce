<?php

namespace OroB2B\Component\Duplicator;


abstract class AbstractFactory
{
    /**
     * @var array
     */
    protected $availableObjectTypes = [];

    /**
     * @param $keyword
     * @param $className
     * @return $this
     */
    public function addObjectType($keyword, $className)
    {
        if (!is_a($className, $this->getSupportedClassName(), true)) {
            throw new \InvalidArgumentException('Class not supported');
        }
        $this->availableObjectTypes[$keyword] = $className;

        return $this;
    }

    /**
     * @param string $keyword
     * @param array $constructorArgs
     * @return object
     */
    public function create($keyword, array $constructorArgs = [])
    {
        if (empty($this->availableObjectTypes[$keyword])) {
            throw new \InvalidArgumentException('Unknown class was requested');
        }

        $class = new \ReflectionClass($this->availableObjectTypes[$keyword]);
        if ($constructorArgs) {
            $instance = $class->newInstanceArgs($constructorArgs);
        } else {
            $instance = $class->newInstance();
        }

        return $instance;
    }

    /**
     * @return string
     */
    abstract protected function getSupportedClassName();
}
