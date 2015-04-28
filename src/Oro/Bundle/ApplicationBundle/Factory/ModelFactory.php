<?php

namespace Oro\Bundle\ApplicationBundle\Factory;

class ModelFactory implements ModelFactoryInterface
{
    /**
     * @var string
     */
    protected $modelClassName;

    /**
     * @var \ReflectionClass
     */
    protected $classReflection;

    /**
     * @param string $modelClassName
     */
    public function __construct($modelClassName)
    {
        $this->modelClassName = $modelClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $arguments = [])
    {
        if (!$this->classReflection) {
            $this->classReflection = new \ReflectionClass($this->modelClassName);
        }

        return $this->classReflection->newInstanceArgs($arguments);
    }
}
