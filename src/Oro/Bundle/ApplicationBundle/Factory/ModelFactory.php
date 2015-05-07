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
        $this->assertModelClassName($modelClassName);

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

    /**
     * @param string $className
     */
    protected function assertModelClassName($className)
    {
        if (!class_exists($className)) {
            throw new \LogicException(sprintf('Class "%s" is not defined', $className));
        }

        if (!is_a($className, 'Oro\Bundle\ApplicationBundle\Model\ModelInterface', true)) {
            throw new \LogicException(sprintf('Class "%s" must implement ModelInterface', $className));
        }
    }
}
