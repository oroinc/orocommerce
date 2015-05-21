<?php

namespace Oro\Bundle\ApplicationBundle\Factory;

class ModelFactory implements ModelFactoryInterface
{
    /**
     * @var string
     */
    protected $modelClassName;

    /**
     * @var string
     */
    protected $entityClassName;

    /**
     * @var \ReflectionClass
     */
    protected $modelClassReflection;

    /**
     * @var \ReflectionClass
     */
    protected $entityClassReflection;

    /**
     * @param string $modelClassName
     * @param string $entityClassName
     */
    public function __construct($modelClassName, $entityClassName)
    {
        $this->assertModelClassName($modelClassName);
        $this->assertEntityClassName($entityClassName);

        $this->modelClassName = $modelClassName;
        $this->entityClassName = $entityClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $arguments = [])
    {
        if (empty($arguments[0])) {
            $arguments[0] = $this->createEntity();
        }

        if (!$this->modelClassReflection) {
            $this->modelClassReflection = new \ReflectionClass($this->modelClassName);
        }

        return $this->modelClassReflection->newInstanceArgs($arguments);
    }

    /**
     * @return object
     */
    protected function createEntity()
    {
        if (!$this->entityClassReflection) {
            $this->entityClassReflection = new \ReflectionClass($this->entityClassName);
        }

        return $this->entityClassReflection->newInstance();
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

    /**
     * @param string $className
     */
    protected function assertEntityClassName($className)
    {
        if (!class_exists($className)) {
            throw new \LogicException(sprintf('Class "%s" is not defined', $className));
        }
    }
}
