<?php

namespace Oro\Component\Testing\Unit\PropertyAccess;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\StringUtil;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class CollectionAccessor
{
    /**
     * @var object
     */
    protected $object;

    /**
     * @var string
     */
    protected $propertyName;

    /**
     * @var PropertyAccessor
     */
    protected $accessor;

    /**
     * @var array
     */
    protected $methods;

    /**
     * @param object $object
     * @param string $propertyName
     */
    public function __construct($object, $propertyName)
    {
        $singulars = (array) StringUtil::singularify($this->camelize($propertyName));

        $this->accessor     = PropertyAccess::createPropertyAccessor();
        $this->methods      = $this->findAdderAndRemover(new \ReflectionClass($object), $singulars);
        $this->object       = $object;
        $this->propertyName = $propertyName;
    }

    /**
     * @return Collection
     */
    public function getItems()
    {
        return $this->accessor->getValue($this->object, $this->propertyName);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function addItem($value)
    {
        return call_user_func([$this->object, $this->getAddItemMethod()], $value);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function removeItem($value)
    {
        return call_user_func([$this->object, $this->getRemoveItemMethod()], $value);
    }

    /**
     * @return string
     */
    public function getAddItemMethod()
    {
        return $this->methods[0];
    }

    /**
     * @return string
     */
    public function getRemoveItemMethod()
    {
        return $this->methods[1];
    }

    /**
     * Searches for add and remove methods.
     *
     * @param \ReflectionClass $reflClass The reflection class for the given object
     * @param array            $singulars The singular form of the property name or null
     *
     * @return array|null An array containing the adder and remover when found, null otherwise
     *
     * @throws NoSuchPropertyException If the property does not exist
     */
    protected function findAdderAndRemover(\ReflectionClass $reflClass, array $singulars)
    {
        foreach ($singulars as $singular) {
            $addMethod      = 'add' . $singular;
            $removeMethod   = 'remove' . $singular;

            $addMethodFound     = $this->isAccessible($reflClass, $addMethod, 1);
            $removeMethodFound  = $this->isAccessible($reflClass, $removeMethod, 1);

            if ($addMethodFound && $removeMethodFound) {
                return [$addMethod, $removeMethod];
            }

            if ($addMethodFound xor $removeMethodFound) {
                throw new NoSuchPropertyException(sprintf(
                    'Found the public method "%s()", but did not find a public "%s()" on class %s',
                    $addMethodFound ? $addMethod : $removeMethod,
                    $addMethodFound ? $removeMethod : $addMethod,
                    $reflClass->name
                ));
            }
        }
    }

    /**
     * Returns whether a method is public and has a specific number of required parameters.
     *
     * @param \ReflectionClass $class      The class of the method
     * @param string           $methodName The method name
     * @param int              $parameters The number of parameters
     *
     * @return bool Whether the method is public and has $parameters
     *              required parameters
     */
    private function isAccessible(\ReflectionClass $class, $methodName, $parameters)
    {
        if ($class->hasMethod($methodName)) {
            $method = $class->getMethod($methodName);

            if ($method->isPublic() && $method->getNumberOfRequiredParameters() === $parameters) {
                return true;
            }
        }

        return false;
    }

    /**
     * Camelizes a given string.
     *
     * @param string $string Some string
     *
     * @return string The camelized version of the string
     */
    protected function camelize($string)
    {
        return preg_replace_callback('/(^|_|\.)+(.)/', function ($match) {
            return ('.' === $match[1] ? '_' : '') . strtoupper($match[2]);
        }, $string);
    }
}
